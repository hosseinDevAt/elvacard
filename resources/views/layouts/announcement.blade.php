@php
    $announcement = \App\Models\Announcement::visible()->first();

    // The stored colors are hex literals (#RGB / #RRGGBB), not utility class
    // names, so they must be applied as inline CSS -- exactly like the homepage
    // section background colors. Rendering them into the class attribute made
    // every admin-selected color a dead class token. The values are re-validated
    // here so a legacy or hand-edited row can never inject arbitrary CSS.
    $announcementBackground = preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', (string) $announcement?->background_color)
        ? $announcement->background_color
        : null;
    $announcementText = preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', (string) $announcement?->text_color)
        ? $announcement->text_color
        : null;

    $announcementStyle = trim(implode(';', array_filter([
        $announcementBackground !== null ? 'background-color: '.$announcementBackground : '',
        $announcementText !== null ? 'color: '.$announcementText : '',
    ])));
@endphp

@if ($announcement && $announcement->title)
    <div
        class="border-b border-gray-200 text-center py-2.5 px-4 sm:px-6 {{ $announcementBackground !== null ? '' : 'bg-primary-50' }} {{ $announcementText !== null ? '' : 'text-primary-900' }}"
        @if ($announcementStyle !== '') style="{{ $announcementStyle }}" @endif
        role="alert"
    >
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4 text-xs sm:text-sm">
            <span class="inline-flex items-center gap-1.5 font-bold">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#ffde5b] border border-amber-600 animate-pulse"></span>
                {{ $announcement->title }}
            </span>

            @if ($announcement->content)
                <span class="text-current opacity-85">
                    {{ $announcement->content }}
                </span>
            @endif

            @if ($announcement->link)
                <a
                    href="{{ safe_url($announcement->link) ?: 'javascript:void(0)' }}"
                    class="font-semibold underline hover:text-[#ffde5b] hover:no-underline whitespace-nowrap transition-colors duration-150"
                    @if (str_starts_with((string) $announcement->link, 'javascript:') || str_starts_with((string) $announcement->link, 'data:'))
                        href="javascript:void(0)"
                    @else
                        rel="noopener noreferrer"
                    @endif
                >
                    مشاهده جزئیات
                </a>
            @endif
        </div>
    </div>
@endif