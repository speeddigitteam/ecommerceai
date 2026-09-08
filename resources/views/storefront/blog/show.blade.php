<x-storefront-layout :title="$post->seo_title ?: $post->title" :description="$post->meta_description ?: $post->excerpt" og-type="article" :canonical="route('storefront.blog.show', $post)">
    @php($readingMinutes = max(1, (int) ceil(str_word_count(strip_tags($post->content ?? '')) / 200)))
    <article class="bg-white">
        <header class="relative overflow-hidden bg-gradient-to-br from-indigo-50 via-sky-50 to-cyan-50">
            <div class="pointer-events-none absolute -left-24 -top-28 h-80 w-80 rounded-full bg-violet-200/35 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-24 -top-20 h-80 w-80 rounded-full bg-sky-200/50 blur-3xl"></div>
            <div class="relative mx-auto grid max-w-[1400px] items-center gap-10 px-4 py-10 sm:px-6 sm:py-14 lg:grid-cols-[minmax(0,.78fr)_minmax(480px,1.22fr)] lg:px-8 lg:py-16">
                <div>
                    <div class="flex flex-wrap gap-2">@foreach($post->categories as $category)<span class="rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-sky-700 shadow-sm ring-1 ring-sky-100">{{ $category->name }}</span>@endforeach</div>
                    <h1 class="mt-5 text-3xl font-bold leading-tight tracking-tight text-slate-900 sm:text-4xl lg:text-[2.65rem]">{{ $post->title }}</h1>

                    <div class="mt-7 flex flex-wrap items-center gap-y-4 text-sm text-slate-600">
                        <div class="flex items-center gap-3 pr-5">
                            @if($post->author?->profile_image_path)
                                <img src="{{ asset('storage/'.$post->author->profile_image_path) }}" alt="{{ $post->author->name }}" class="h-12 w-12 rounded-full object-cover ring-4 ring-white/70">
                            @else
                                <span class="grid h-12 w-12 place-items-center rounded-full bg-white text-base font-black text-indigo-600 shadow-sm">{{ str($post->author?->name ?: 'Store')->substr(0, 1)->upper() }}</span>
                            @endif
                            <div><span class="block text-xs text-slate-500">Written by</span><strong class="mt-0.5 block text-sm text-slate-900">{{ $post->author?->name ?: 'Store team' }}</strong></div>
                        </div>
                        <div class="border-l border-slate-300/70 px-5"><span class="block text-xs text-slate-500">Published on</span><time class="mt-1 block font-semibold text-slate-900" datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('d.m.Y') }}</time></div>
                        <div class="border-l border-slate-300/70 pl-5"><span class="block text-xs text-slate-500">Time to read</span><strong class="mt-1 block text-slate-900">{{ $readingMinutes }} min</strong></div>
                    </div>

                    <nav aria-label="Breadcrumb" class="mt-7 flex flex-wrap items-center gap-1 text-sm leading-6">
                        <a href="{{ route('storefront.index') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Home</a><span class="text-slate-400">&raquo;</span>
                        <a href="{{ route('storefront.blog.index') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Blog</a>
                        @if($post->categories->first())<span class="text-slate-400">&raquo;</span><span class="text-indigo-600">{{ $post->categories->first()->name }}</span>@endif
                        <span class="text-slate-400">&raquo;</span><span class="text-slate-700">{{ $post->title }}</span>
                    </nav>
                </div>

                <div class="group overflow-hidden rounded-3xl bg-white/60 p-2 shadow-xl shadow-indigo-200/30 ring-1 ring-white/80">
                    @if($post->featured_image_path)
                        <img src="{{ asset('storage/'.$post->featured_image_path) }}" alt="{{ $post->title }}" class="aspect-[16/9] w-full rounded-2xl object-cover transition duration-500 ease-out group-hover:scale-[1.03] group-hover:brightness-105">
                    @else
                        <div class="grid aspect-[16/9] place-items-center rounded-2xl bg-gradient-to-br from-indigo-100 to-sky-100 text-sm font-bold text-indigo-400">Blog featured image</div>
                    @endif
                </div>
            </div>
        </header>
        <div class="mx-auto grid max-w-[1400px] items-start gap-8 px-4 py-10 sm:px-6 sm:py-14 lg:px-8 xl:grid-cols-[250px_minmax(0,1fr)_290px]">
            <aside class="order-2 rounded-2xl border border-slate-200 bg-slate-50 p-5 xl:order-1 xl:sticky xl:top-24 xl:border-0 xl:bg-transparent xl:p-0">
                <p class="text-lg font-extrabold text-slate-900">{{ $readingMinutes }} Min Read</p>
                <div class="mt-3 h-1 overflow-hidden rounded-full bg-slate-200"><div class="h-full w-1/4 rounded-full bg-indigo-500"></div></div>
                <nav id="blog-table-of-contents" aria-label="Table of contents" class="mt-6 grid gap-4 text-sm leading-6 text-slate-600">
                    <p class="text-sm text-slate-400">Article sections will appear here.</p>
                </nav>
            </aside>

            <div class="order-1 min-w-0 xl:order-2">
                @if($post->excerpt)<p class="mb-8 rounded-2xl bg-sky-50 p-5 text-lg leading-8 text-slate-700 sm:p-7">{{ $post->excerpt }}</p>@endif
                <div id="blog-article-content" class="max-w-none text-base leading-8 text-slate-700 [&_a]:font-semibold [&_a]:text-indigo-600 [&_a]:underline-offset-4 hover:[&_a]:underline [&_blockquote]:my-7 [&_blockquote]:rounded-r-2xl [&_blockquote]:border-l-4 [&_blockquote]:border-indigo-400 [&_blockquote]:bg-indigo-50 [&_blockquote]:px-6 [&_blockquote]:py-4 [&_h1]:mb-6 [&_h1]:mt-10 [&_h1]:scroll-mt-24 [&_h1]:text-4xl [&_h1]:font-black [&_h1]:leading-tight [&_h1]:tracking-tight [&_h1]:text-slate-950 [&_h2]:mb-5 [&_h2]:mt-10 [&_h2]:scroll-mt-24 [&_h2]:text-3xl [&_h2]:font-extrabold [&_h2]:leading-tight [&_h2]:tracking-tight [&_h2]:text-slate-900 [&_h3]:mb-4 [&_h3]:mt-8 [&_h3]:scroll-mt-24 [&_h3]:text-2xl [&_h3]:font-bold [&_h3]:leading-snug [&_h3]:text-slate-900 [&_h4]:mb-3 [&_h4]:mt-7 [&_h4]:text-xl [&_h4]:font-bold [&_h4]:text-slate-900 [&_li]:my-2 [&_ol]:my-6 [&_ol]:list-decimal [&_ol]:pl-7 [&_p]:my-5 [&_strong]:font-bold [&_strong]:text-slate-900 [&_table]:my-8 [&_table]:w-full [&_ul]:my-6 [&_ul]:list-disc [&_ul]:pl-7">{!! $post->content !!}</div>
                @if($post->gallery_paths)<div class="mt-10 grid grid-cols-2 gap-4">@foreach($post->gallery_paths as $image)<div class="group overflow-hidden rounded-2xl"><img src="{{ asset('storage/'.$image) }}" alt="" class="aspect-video w-full object-cover transition duration-500 ease-out group-hover:scale-105 group-hover:brightness-105"></div>@endforeach</div>@endif
                <a href="{{ route('storefront.blog.index') }}" class="mt-10 inline-flex items-center gap-2 text-sm font-bold text-sky-600">&larr; Back to blog</a>
            </div>

            <aside class="order-3 overflow-hidden rounded-3xl bg-slate-950 text-white shadow-xl xl:sticky xl:top-24">
                @if($post->featured_image_path)<div class="group overflow-hidden"><img src="{{ asset('storage/'.$post->featured_image_path) }}" alt="" class="aspect-[4/3] w-full object-cover transition duration-500 ease-out group-hover:scale-105 group-hover:brightness-105"></div>@else<div class="aspect-[4/3] bg-gradient-to-br from-pink-400 via-violet-500 to-indigo-600"></div>@endif
                <div class="p-5 sm:p-6">
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-sky-300">Keep reading</p>
                    <h2 class="mt-3 text-2xl font-black leading-tight">Related articles</h2>
                    <div class="mt-5 grid gap-4">
                        @forelse($relatedPosts as $relatedPost)
                            <a href="{{ route('storefront.blog.show', $relatedPost) }}" class="group border-t border-white/15 pt-4">
                                <span class="line-clamp-2 text-sm font-semibold leading-6 text-slate-200 transition group-hover:text-white">{{ $relatedPost->title }}</span>
                                <span class="mt-1 block text-xs text-slate-400">{{ $relatedPost->published_at?->format('d M Y') }}</span>
                            </a>
                        @empty
                            <p class="border-t border-white/15 pt-4 text-sm leading-6 text-slate-300">More helpful stories are coming soon.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('storefront.blog.index') }}" class="mt-6 flex w-full items-center justify-center rounded-xl bg-violet-500 px-4 py-3 text-sm font-bold transition hover:bg-violet-400">Explore all blogs &rarr;</a>
                </div>
            </aside>
        </div>
    </article>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const content = document.querySelector('#blog-article-content');
            const table = document.querySelector('#blog-table-of-contents');
            if (!content || !table) return;
            const headings = [...content.querySelectorAll('h2, h3')];
            if (!headings.length) return;
            const usedIds = new Set();
            table.replaceChildren(...headings.map((heading, index) => {
                let id = heading.id || heading.textContent.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || `section-${index + 1}`;
                const baseId = id;
                let suffix = 2;
                while (usedIds.has(id)) id = `${baseId}-${suffix++}`;
                usedIds.add(id);
                heading.id = id;
                const link = document.createElement('a');
                link.href = `#${id}`;
                link.textContent = heading.textContent;
                link.className = heading.tagName === 'H3' ? 'pl-4 transition hover:text-indigo-600' : 'font-medium transition hover:text-indigo-600';
                return link;
            }));
        });
    </script>
</x-storefront-layout>
