<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontBlogController extends Controller
{
    public function index(Request $request): View
    {
        $posts = BlogPost::query()
            ->with(['categories', 'author'])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($request->filled('search'), fn ($query) => $query->where(function ($searchQuery) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $searchQuery->where('title', 'like', $term)
                    ->orWhere('excerpt', 'like', $term)
                    ->orWhere('content', 'like', $term);
            }))
            ->when($request->filled('category'), fn ($query) => $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->where('slug', $request->string('category'))))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $categories = BlogCategory::query()
            ->whereHas('posts', fn ($query) => $query
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now()))
            ->orderBy('name')
            ->get();

        return view('storefront.blog.index', ['posts' => $posts, 'categories' => $categories]);
    }

    public function show(BlogPost $blogPost): View
    {
        abort_unless(
            $blogPost->status === 'published'
            && $blogPost->visibility === 'public'
            && $blogPost->published_at?->isPast(),
            404
        );

        $blogPost->load(['categories', 'author']);
        $relatedPosts = BlogPost::query()
            ->with('categories')
            ->whereKeyNot($blogPost->id)
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when(
                $blogPost->categories->isNotEmpty(),
                fn ($query) => $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereKey($blogPost->categories->modelKeys()))
            )
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('storefront.blog.show', ['post' => $blogPost, 'relatedPosts' => $relatedPosts]);
    }
}
