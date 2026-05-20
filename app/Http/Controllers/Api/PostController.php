<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        return Post::latest()->paginate();
    }

    public function services()
    {
        return Post::services()
        ->with('categories')
            ->published()
            ->latest()
            ->paginate();
    }

    public function projects()
    {
        return Post::projects()
            ->with('categories')
            ->published()
            ->latest()
            ->paginate();
    }

    public function show(Post $post)
    {
        return $post->load('categories');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Post::class);

        $validated = $request->validate([
            'type' => 'required|in:service,project',
            'title' => 'required|string|max:255',
            'description' => 'required|string',

            'price' => 'nullable|numeric|required_if:type,service',

            'budget' => 'nullable|numeric|required_if:type,project',
            'address' => 'nullable|string|required_if:type,project',

            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $post = Post::create([
            'user_id' => Auth::user()->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'price' => $validated['price'] ?? null,
            'budget' => $validated['budget'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => 'draft',
        ]);

        if (
            $post->isProject() &&
            isset($validated['categories'])
        ) {
            $post->categories()->sync($validated['catiegores']);
        }

        return response()->json($post->load('categories'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $post->update($request->all());

        return response()->json($post);
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json([
            'message' => 'Deleted'
        ]);
    }

    public function publish(Post $post)
    {
        $this->authorize('publish', $post);

        $post->update([
            'status' => 'published'
        ]);

        return response()->json([
            'message' => 'Published'
        ]);
    }

    public function pause(Post $post)
    {
        $this->authorize('pause', $post);

        $post->update([
            'status' => 'paused'
        ]);

        return response()->json([
            'message' => 'Paused'
        ]);
    }

    public function archive(Post $post)
    {
        $this->authorize('archive', $post);

        $post->update([
            'status' => 'archived'
        ]);

        return response()->json([
            'message' => 'Archived'
        ]);
    }

    public function close(Post $post)
    {
        $this->authorize('close', $post);

        $post->update([
            'status' => 'closed'
        ]);

        return response()->json([
            'message' => 'Closed'
        ]);
    }
}