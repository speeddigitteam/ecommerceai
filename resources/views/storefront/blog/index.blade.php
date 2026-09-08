<x-storefront-layout title="Blog" description="Read our latest articles, guides and updates.">
    <section class="relative overflow-hidden bg-[#080b16] text-white">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_12%_85%,rgba(16,185,129,.35),transparent_32%),radial-gradient(circle_at_82%_15%,rgba(124,58,237,.34),transparent_32%),linear-gradient(135deg,rgba(15,23,42,.9),rgba(3,7,18,.82))]"></div>
        <div class="pointer-events-none absolute -left-20 top-20 h-44 w-44 rotate-45 rounded-3xl border-[18px] border-cyan-400/20 shadow-[0_0_50px_rgba(34,211,238,.25)]"></div>
        <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rotate-12 rounded-full border-[22px] border-violet-400/20 shadow-[0_0_60px_rgba(167,139,250,.28)]"></div>
        <div class="relative mx-auto max-w-[1400px] px-4 py-12 text-center sm:px-6 sm:py-16 lg:px-8 lg:py-20">
            <nav aria-label="Breadcrumb" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-semibold text-slate-300 ring-1 ring-white/10"><a href="{{ route('storefront.index') }}" class="transition hover:text-white">Home</a><span>&rsaquo;</span><span class="text-white">Blogs</span></nav>
            <h1 class="mt-6 text-3xl font-black leading-tight tracking-tight sm:text-5xl lg:text-6xl">Your Go-To Source:<span class="mt-2 block font-serif italic text-white">Blog Highlights &amp; More</span></h1>
            <form method="GET" action="{{ route('storefront.blog.index') }}" class="mx-auto mt-9 flex max-w-2xl items-center rounded-xl bg-white p-1.5 text-slate-900 shadow-2xl shadow-black/25 ring-2 ring-violet-500/70">
                <svg class="ml-3 h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m20 20-4-4"/></svg>
                <input name="search" value="{{ request('search') }}" type="search" placeholder="Search any blog" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base focus:ring-0">
                @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                <button class="rounded-lg bg-violet-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-violet-500">Search</button>
            </form>
        </div>
    </section>

    <section class="bg-slate-50 px-4 py-7 sm:px-6 lg:px-8">
        <nav class="mx-auto flex max-w-[1400px] gap-2 overflow-x-auto pb-1" aria-label="Blog categories">
            <a href="{{ route('storefront.blog.index', array_filter(['search' => request('search')])) }}" class="shrink-0 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request('category') ? 'bg-slate-50 text-slate-700 hover:bg-slate-100' : 'bg-violet-600 text-white shadow-lg shadow-violet-500/20' }}">Latest Blogs</a>
            @foreach($categories as $category)
                <a href="{{ route('storefront.blog.index', array_filter(['category' => $category->slug, 'search' => request('search')])) }}" class="shrink-0 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request('category') === $category->slug ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20' : 'bg-slate-50 text-slate-700 hover:bg-slate-100' }}">{{ $category->name }}</a>
            @endforeach
        </nav>
    </section>

    <section class="bg-slate-50 px-4 py-10 sm:px-6 sm:py-14 lg:px-8 lg:py-16">
        <div class="mx-auto max-w-[1400px]">
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($posts as $post)
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <a href="{{ route('storefront.blog.show', $post) }}" class="group block overflow-hidden">
                            @if($post->featured_image_path)
                                <img src="{{ asset('storage/'.$post->featured_image_path) }}" alt="{{ $post->title }}" class="aspect-[16/9] w-full object-cover transition duration-500 ease-out group-hover:scale-105 group-hover:brightness-105">
                            @else
                                <div class="grid aspect-[16/9] place-items-center bg-slate-100 text-sm font-semibold text-slate-400">Blog image</div>
                            @endif
                        </a>
                        <div class="p-5 sm:p-6">
                            <div class="flex flex-wrap gap-2">@foreach($post->categories as $category)<span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $category->name }}</span>@endforeach</div>
                            <h2 class="mt-4 text-xl font-bold leading-7 text-slate-950"><a href="{{ route('storefront.blog.show', $post) }}" class="transition hover:text-sky-600">{{ $post->title }}</a></h2>
                            @if($post->excerpt)<p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">{{ $post->excerpt }}</p>@endif
                            <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-xs text-slate-400"><span>{{ $post->author?->name ?: 'Store team' }}</span><time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('d M Y') }}</time></div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center sm:col-span-2 xl:col-span-3"><h2 class="text-xl font-bold">No blog posts yet</h2><p class="mt-2 text-sm text-slate-500">Published articles will appear here.</p></div>
                @endforelse
            </div>
            @if($posts->hasPages())<div class="mt-8">{{ $posts->links() }}</div>@endif
        </div>
    </section>
</x-storefront-layout>
