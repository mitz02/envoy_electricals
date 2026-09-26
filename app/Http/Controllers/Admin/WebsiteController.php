<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    protected array $fields = [
        'business' => [
            'business.name' => 'text',
            'business.email' => 'email',
            'business.phone' => 'text',
            'business.address' => 'textarea',
        ],
        'website' => [
            'website.announcement' => 'text',
            'website.hero_title' => 'text',
            'website.hero_subtitle' => 'textarea',
            'website.about_summary' => 'textarea',
            'website.whatsapp' => 'text',
            'website.instagram' => 'text',
            'website.facebook' => 'text',
        ],
        'sales' => [
            'tax.rate' => 'number',
            'inventory.allow_negative' => 'boolean',
        ],
        'bank' => [
            'bank.account_name' => 'text',
            'bank.account_number' => 'text',
            'bank.bank_name' => 'text',
            'bank.instructions' => 'textarea',
        ],
    ];

    public function index(): Response
    {
        return Inertia::render('Admin/Website/Settings', [
            'fields' => $this->fields,
            'values' => $this->loadValues(),
            'mediaCount' => Media::count(),
            'groupLabels' => [
                'business' => 'Business Details',
                'website' => 'Website Content',
                'sales' => 'Sales & Inventory',
                'bank' => 'Bank Payment Details',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'values' => ['array'],
            'values.*' => ['nullable'],
        ]);

        foreach ($validated['values'] as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            Setting::updateOrCreate(['key' => $key], ['value' => (string) ($value ?? ''), 'group' => Str::before($key, '.')]);
        }

        AuditLogger::log('updated', 'settings', null, 'Updated business & website settings');

        return redirect()->back()->with('success', 'Settings saved.');
    }

    // ---------- Media Library ----------

    public function media(Request $request): Response
    {
        $media = Media::query()
            ->with('uploader:id,name')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('caption', 'like', "%{$s}%"))
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->latest('id')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Admin/Media/Index', [
            'media' => $media,
            'categories' => Media::distinct()->whereNotNull('category')->orderBy('category')->pluck('category'),
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    public function mediaStore(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $validated['file'];

        $path = $file->store('media', 'public');

        $media = Media::create([
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => $path,
            'type' => Str::startsWith($file->getMimeType() ?? '', 'image/') ? 'image' : 'document',
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'category' => $request->input('category') ?: null,
            'alt' => null,
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLogger::log('created', 'media', $media->id, "Uploaded {$path} to media library");

        return redirect()->back()->with('success', 'File uploaded to media library.');
    }

    public function mediaUpdate(Request $request, Media $media)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $media->update($validated);

        AuditLogger::log('updated', 'media', $media->id, "Updated media {$media->name}");

        return redirect()->back()->with('success', 'Media details updated.');
    }

    public function mediaDestroy(Media $media)
    {
        $name = $media->name;
        $media->delete();

        AuditLogger::log('deleted', 'media', $media->id, "Removed media {$name}");

        return redirect()->back()->with('success', 'Media removed.');
    }

    protected function loadValues(): array
    {
        $out = [];
        foreach (array_keys($this->fields) as $group) {
            foreach ($this->fields[$group] as $key => $type) {
                $setting = Setting::where('key', $key)->first();
                $out[$key] = $type === 'boolean'
                    ? (bool) ($setting->value ?? false)
                    : ($setting->value ?? '');
            }
        }

        return $out;
    }
}
