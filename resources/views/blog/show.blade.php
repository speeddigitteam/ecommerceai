<x-admin-layout title="Blog Preview">
    <div class="ds-page"><x-admin-sidebar /><main class="min-w-0 lg:pl-72"><x-admin-topbar /><div class="mx-auto max-w-5xl p-5 sm:p-8">
        <div class="mb-6 flex items-center justify-between"><div><a href="{{ route('blog.index') }}" class="text-sm font-semibold text-indigo-600">&larr; Blog</a><h1 class="mt-2 text-2xl font-bold">Blog preview</h1></div><a href="{{ route('blog.edit',$post) }}" class="ds-button-primary">Edit blog</a></div>
        <article class="ds-card overflow-hidden">
            @if($post->featured_image_path)<img src="{{ asset('storage/'.$post->featured_image_path) }}" alt="{{ $post->title }}" class="max-h-[520px] w-full object-cover">@endif
            <div class="p-6 sm:p-10">
                <div class="flex flex-wrap gap-2"><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ ucfirst($post->status) }}</span>@foreach($post->categories as $category)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs">{{ $category->name }}</span>@endforeach</div>
                <h2 class="mt-5 text-4xl font-bold">{{ $post->title }}</h2>
                <p class="mt-3 text-sm text-slate-500">By {{ $post->author?->name ?: 'Unknown' }} &middot; {{ $post->published_at?->format('d M Y, h:i A') ?: 'Not published' }}</p>
                @if($post->excerpt)<p class="mt-6 text-lg leading-8 text-slate-600">{{ $post->excerpt }}</p>@endif
                @if($post->content)<div class="prose mt-8 max-w-none dark:prose-invert">{!! $post->content !!}</div>@endif
                @if($post->gallery_paths)<div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3">@foreach($post->gallery_paths as $image)<img src="{{ asset('storage/'.$image) }}" alt="" class="aspect-video rounded-xl object-cover">@endforeach</div>@endif
            </div>
        </article>
    </div></main></div>
</x-admin-layout>
