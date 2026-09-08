<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlogPostRequest;
use App\Http\Requests\UpdateBlogPostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\MediaLibrary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private readonly MediaLibrary $mediaLibrary) {}

    public function index(): View
    {
        return view('blog.index', ['posts' => BlogPost::query()->with(['categories', 'author'])->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('blog.form', ['post' => new BlogPost, 'categories' => $this->categories()]);
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $post = BlogPost::query()->create($this->data($request));
        $post->categories()->sync($request->validated('category_ids', []));
        $this->storeMedia($request, $post);

        return to_route('blog.index')->with('status', 'Blog post created successfully.');
    }

    public function show(BlogPost $blogPost): View
    {
        return view('blog.show', ['post' => $blogPost->load(['categories', 'author'])]);
    }

    public function edit(BlogPost $blogPost): View
    {
        return view('blog.form', ['post' => $blogPost, 'categories' => $this->categories()]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $blogPost->update($this->data($request, $blogPost));
        $blogPost->categories()->sync($request->validated('category_ids', []));
        $this->storeMedia($request, $blogPost);

        return to_route('blog.index')->with('status', 'Blog post updated successfully.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $this->mediaLibrary->delete([$blogPost->featured_image_path, ...($blogPost->gallery_paths ?? [])]);
        $blogPost->delete();

        return to_route('blog.index')->with('status', 'Blog post deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function data(StoreBlogPostRequest $request, ?BlogPost $post = null): array
    {
        $data = $request->safe()->except(['featured_image', 'gallery', 'category_ids']);
        $data['author_id'] = $post?->author_id ?: $request->user()->id;
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        $data['tags'] = array_values(array_filter(array_map('trim', explode(',', $data['tags'] ?? ''))));
        $data['published_at'] = $data['status'] === 'published' ? (($data['published_at'] ?? null) ?: $post?->published_at ?: now()) : null;

        return $data;
    }

    private function storeMedia(StoreBlogPostRequest $request, BlogPost $post): void
    {
        if ($request->hasFile('featured_image')) {
            $this->mediaLibrary->delete($post->featured_image_path);
            $post->featured_image_path = $this->mediaLibrary->storeImage($request->file('featured_image'), 'blog', $post->title);
        }
        if ($request->hasFile('gallery')) {
            $post->gallery_paths = [...($post->gallery_paths ?? []), ...array_map(fn (UploadedFile $file): string => $this->mediaLibrary->storeImage($file, 'blog/gallery', $post->title), $request->file('gallery'))];
        }
        $post->save();
    }

    /** @return Collection<int, BlogCategory> */
    private function categories(): Collection
    {
        return BlogCategory::query()->whereNull('parent_id')->with('childrenRecursive')->orderBy('name')->get();
    }
}
