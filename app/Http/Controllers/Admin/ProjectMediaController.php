<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMedia;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectMediaController extends Controller
{
    public function store(Request $request, Project $project): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,pdf,doc,docx', 'max:20480'],
            'stage' => ['nullable', 'string', 'max:50'],
            'caption' => ['nullable', 'string', 'max:255'],
            'published' => ['nullable', 'boolean'],
        ]);

        $folder = 'project-media/' . $project->ref_id;
        $uploaded = 0;

        foreach ($request->file('files') as $file) {
            $path = $file->store($folder, 'public');

            $mime = (string) $file->getMimeType();
            $type = str_starts_with($mime, 'image/') ? 'image'
                : (str_starts_with($mime, 'video/') ? 'video' : 'document');

            ProjectMedia::create([
                'project_id' => $project->id,
                'type' => $type,
                'path' => $path,
                'caption' => $data['caption'] ?? null,
                'stage' => $data['stage'] ?? null,
                'published' => $request->boolean('published'),
            ]);

            $uploaded++;
        }

        AuditLogger::log('uploaded', 'project_media', $project->id,
            "Uploaded {$uploaded} media item(s) to project {$project->ref_id}");

        return back()->with('success', $uploaded . ' media item(s) uploaded.');
    }

    public function publish(Request $request, Project $project, ProjectMedia $media): \Illuminate\Http\RedirectResponse
    {
        if ($media->project_id !== $project->id) {
            abort(404);
        }

        $media->update(['published' => $request->boolean('published')]);

        AuditLogger::log('project_media_publish', 'project_media', $media->id,
            ($media->published ? 'Published' : 'Unpublished') . " media on project {$project->ref_id}");

        return back()->with('success', $media->published ? 'Media published to website.' : 'Media unpublished.');
    }

    public function destroy(Request $request, Project $project, ProjectMedia $media): \Illuminate\Http\RedirectResponse
    {
        if ($media->project_id !== $project->id) {
            abort(404);
        }

        Storage::disk('public')->delete($media->path);
        $media->delete();

        AuditLogger::log('deleted', 'project_media', $media->id,
            "Deleted media from project {$project->ref_id}");

        return back()->with('success', 'Media removed.');
    }
}