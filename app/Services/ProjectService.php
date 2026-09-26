<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectMaterial;
use App\Models\ProjectPayment;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Central engine for projects. Every write that touches costs, balances or
 * inventory must flow through here so the project P&L stays consistent and
 * stock stays in sync (spec §14, §37, §38).
 */
class ProjectService
{
    public function __construct(protected InventoryService $inventory) {}

    /**
     * Create a project with a generated ref and zeroed financial state.
     */
    public function create(array $data, int $userId): Project
    {
        $project = DB::transaction(function () use ($data, $userId) {
            $contract = round((float) ($data['contract_value'] ?? 0), 2);
            $storeId = (int) ($data['store_id']
                ?? session('admin_store_id')
                ?? auth()->user()?->store_id
                ?? Store::where('is_default', true)->value('id')
                ?? 1);

            $project = Project::create([
                'ref_id' => ReferenceGenerator::generate('project'),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'location' => $data['location'] ?? null,
                'store_id' => $storeId,
                'contract_value' => $contract,
                'status' => $data['status'] ?? Project::STATUS_DRAFT,
                'start_date' => $data['start_date'] ?? null,
                'expected_completion_date' => $data['expected_completion_date'] ?? null,
                'assigned_user_id' => $data['assigned_user_id'] ?? null,
                'technician_user_id' => $data['technician_user_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'material_cost' => 0,
                'labour_cost' => 0,
                'transport_cost' => 0,
                'other_cost' => 0,
                'project_cost' => 0,
                'amount_received' => 0,
                'balance' => $contract,
                'gross_profit' => $contract,
                'created_by' => $userId,
            ]);

            $this->recompute($project);

            AuditLogger::log('created', 'project', $project->id, "Created project {$project->ref_id} - {$project->name}");

            return $project;
        });

        return $project;
    }

    public function update(Project $project, array $data, int $userId): Project
    {
        DB::transaction(function () use ($project, $data) {
            $project->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'location' => $data['location'] ?? null,
                'contract_value' => round((float) $data['contract_value'], 2),
                'status' => $data['status'] ?? $project->status,
                'start_date' => $data['start_date'] ?? null,
                'expected_completion_date' => $data['expected_completion_date'] ?? null,
                'assigned_user_id' => $data['assigned_user_id'] ?? null,
                'technician_user_id' => $data['technician_user_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $project = $this->syncCompletionDate($project, (string) $project->status);
            $this->recompute($project);

            AuditLogger::log('updated', 'project', $project->id, "Updated project {$project->ref_id} - {$project->name}");
        });

        return $project->fresh();
    }

    /**
     * Change status. Transitioning to "completed" stamps the completion date;
     * leaving "completed" clears it so the project can be re-worked.
     */
    public function updateStatus(Project $project, string $status, int $userId): void
    {
        if (! in_array($status, Project::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Invalid project status selected.']);
        }

        if ($project->status === $status) {
            return;
        }

        DB::transaction(function () use ($project, $status) {
            $project->update(['status' => $status]);
            $this->syncCompletionDate($project, $status);
            $this->recompute($project);

            AuditLogger::log('project_status', 'project', $project->id, "Project {$project->ref_id} status changed to {$status}");
        });
    }

    /**
     * Delete a project. All materials already issued to it are returned to
     * inventory first so no stock is lost (transaction safety, §38).
     */
    public function destroy(Project $project, int $userId): void
    {
        DB::transaction(function () use ($project, $userId) {
            foreach ($project->materials()->where('issued_to_inventory', true)->get() as $material) {
                $this->returnStock($project, $material, $userId);
            }

            $project->delete();

            AuditLogger::log('deleted', 'project', $project->id, "Deleted project {$project->ref_id} - {$project->name}");
        });
    }

    // ------------------------------------------------------------------
    // Materials
    // ------------------------------------------------------------------

    /**
     * Attach a material to the project. When issueNow is set the stock is
     * deducted from inventory in the same operation.
     */
    public function addMaterial(Project $project, array $data, int $userId): ProjectMaterial
    {
        $product = Product::findOrFail($data['product_id']);

        $quantity = (int) $data['quantity'];
        $unitCost = isset($data['unit_cost']) && $data['unit_cost'] !== null && $data['unit_cost'] !== ''
            ? round((float) $data['unit_cost'], 2)
            : round((float) $product->average_cost, 2);

        return DB::transaction(function () use ($project, $product, $quantity, $unitCost, $data, $userId) {
            $material = ProjectMaterial::create([
                'project_id' => $project->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total' => round($quantity * $unitCost, 2),
            ]);

            if (! empty($data['issue_now'])) {
                $this->issueMaterial($project, $material, $userId);
            } else {
                $this->recompute($project);
            }

            AuditLogger::log('created', 'project_material', $material->id,
                "Added {$quantity} x {$product->name} to project {$project->ref_id}");

            return $material;
        });
    }

    /**
     * Issue a material to the project: inventory decreases, movement ledger
     * records project_issue, and project cost is updated (§14, TEST 4).
     */
    public function issueMaterial(Project $project, ProjectMaterial $material, int $userId): void
    {
        if ($material->project_id !== $project->id) {
            abort(404);
        }

        if ($material->issued_to_inventory) {
            return;
        }

        DB::transaction(function () use ($project, $material, $userId) {
            $this->inventory->outbound(
                $material->product,
                $material->quantity,
                StockMovement::TYPE_PROJECT_ISSUE,
                reference: $project->ref_id.'|MAT-'.$material->id,
                reason: "Material issued to project {$project->ref_id}",
                documentType: 'project',
                documentId: $project->id,
                userId: $userId,
                storeId: $project->store_id,
            );

            $material->update([
                'issued_to_inventory' => true,
                'issued_date' => today(),
            ]);

            $this->recompute($project);

            AuditLogger::log('project_material_issue', 'project_material', $material->id,
                "Issued {$material->quantity} x {$material->product->name} to project {$project->ref_id}");
        });
    }

    /**
     * Remove a material from a project. If it was already issued, the stock is
     * returned to inventory (project_return movement on the ledger).
     */
    public function removeMaterial(Project $project, ProjectMaterial $material, int $userId): void
    {
        if ($material->project_id !== $project->id) {
            abort(404);
        }

        DB::transaction(function () use ($project, $material, $userId) {
            if ($material->issued_to_inventory) {
                $this->returnStock($project, $material, $userId);
            }

            $material->delete();
            $this->recompute($project);

            AuditLogger::log('deleted', 'project_material', $material->id,
                "Removed material from project {$project->ref_id}");
        });
    }

    // ------------------------------------------------------------------
    // Payments
    // ------------------------------------------------------------------

    public function recordPayment(Project $project, array $data, int $userId): ProjectPayment
    {
        $amount = round((float) $data['amount'], 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        $balance = round((float) $project->balance, 2);
        if ($amount > $balance + 0.01) {
            throw ValidationException::withMessages([
                'amount' => 'Payment cannot exceed the outstanding balance of ₦'.number_format(max($balance, 0), 2, '.', ',').'.',
            ]);
        }

        return DB::transaction(function () use ($project, $data, $amount) {
            $payment = ProjectPayment::create([
                'ref_id' => ReferenceGenerator::generate('project_payment'),
                'project_id' => $project->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->recompute($project);

            AuditLogger::log('created', 'project_payment', $payment->id,
                "Recorded project payment of {$amount} on {$project->ref_id}");

            return $payment;
        });
    }

    public function deletePayment(Project $project, ProjectPayment $payment, int $userId): void
    {
        if ($payment->project_id !== $project->id) {
            abort(404);
        }

        DB::transaction(function () use ($project, $payment) {
            $payment->delete();
            $this->recompute($project);

            AuditLogger::log('deleted', 'project_payment', $payment->id,
                "Deleted project payment on {$project->ref_id}");
        });
    }

    // ------------------------------------------------------------------
    // Expenses
    // ------------------------------------------------------------------

    public function addExpense(Project $project, array $data, int $userId): ProjectExpense
    {
        $amount = round((float) $data['amount'], 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Expense amount must be greater than zero.']);
        }

        if (! in_array($data['expense_type'], [ProjectExpense::TYPE_LABOUR, ProjectExpense::TYPE_TRANSPORT, ProjectExpense::TYPE_OTHER], true)) {
            throw ValidationException::withMessages(['expense_type' => 'Invalid expense type selected.']);
        }

        return DB::transaction(function () use ($project, $data, $amount, $userId) {
            $expense = ProjectExpense::create([
                'ref_id' => ReferenceGenerator::generate('project_expense'),
                'project_id' => $project->id,
                'expense_type' => $data['expense_type'],
                'amount' => $amount,
                'expense_date' => $data['expense_date'],
                'payee' => $data['payee'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $this->recompute($project);

            AuditLogger::log('created', 'project_expense', $expense->id,
                "Recorded {$data['expense_type']} expense of {$amount} on {$project->ref_id}");

            return $expense;
        });
    }

    public function deleteExpense(Project $project, ProjectExpense $expense, int $userId): void
    {
        if ($expense->project_id !== $project->id) {
            abort(404);
        }

        DB::transaction(function () use ($project, $expense) {
            $expense->delete();
            $this->recompute($project);

            AuditLogger::log('deleted', 'project_expense', $expense->id,
                "Deleted project expense on {$project->ref_id}");
        });
    }

    // ------------------------------------------------------------------
    // Recalculation engine
    // ------------------------------------------------------------------

    /**
     * Project cost = materials (issued) + labour + transportation + other.
     * Gross profit = contract value - project cost (§37). Balance is derived
     * from recorded payments so the stored numbers never drift.
     */
    public function recompute(Project $project): void
    {
        $material = round((float) $project->materials()->where('issued_to_inventory', true)->sum('total'), 2);
        $labour = round((float) $project->expenses()->where('expense_type', ProjectExpense::TYPE_LABOUR)->sum('amount'), 2);
        $transport = round((float) $project->expenses()->where('expense_type', ProjectExpense::TYPE_TRANSPORT)->sum('amount'), 2);
        $other = round((float) $project->expenses()->where('expense_type', ProjectExpense::TYPE_OTHER)->sum('amount'), 2);

        $cost = round($material + $labour + $transport + $other, 2);
        $contract = round((float) $project->contract_value, 2);
        $received = round((float) $project->payments()->sum('amount'), 2);

        $project->forceFill([
            'material_cost' => $material,
            'labour_cost' => $labour,
            'transport_cost' => $transport,
            'other_cost' => $other,
            'project_cost' => $cost,
            'amount_received' => $received,
            'balance' => round($contract - $received, 2),
            'gross_profit' => round($contract - $cost, 2),
        ])->save();
    }

    protected function returnStock(Project $project, ProjectMaterial $material, int $userId): void
    {
        $this->inventory->inbound(
            $material->product,
            $material->quantity,
            StockMovement::TYPE_PROJECT_RETURN,
            reference: $project->ref_id.'|MAT-'.$material->id.'|RET',
            unitCost: (float) $material->unit_cost,
            reason: "Material returned from project {$project->ref_id}",
            documentType: 'project',
            documentId: $project->id,
            userId: $userId,
            preserveAverage: true,
            storeId: $project->store_id,
        );

        AuditLogger::log('project_material_return', 'project_material', $material->id,
            "Returned {$material->quantity} x {$material->product->name} from project {$project->ref_id}");
    }

    protected function syncCompletionDate(Project $project, string $status): Project
    {
        if ($status === Project::STATUS_COMPLETED) {
            if ($project->completion_date === null) {
                $project->completion_date = today();
                $project->save();
            }
        } elseif ($project->completion_date !== null && $project->wasChanged('status')) {
            $project->completion_date = null;
            $project->save();
        }

        return $project;
    }
}
