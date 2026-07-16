{{-- Admin section navigation. Pass $active ('dashboard' | 'users') to highlight. --}}
@php($active = $active ?? '')
<aside class="w-full shrink-0 sm:w-52">
    <nav class="flex gap-1 sm:flex-col">
        <a href="/admin"
           class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium {{ $active === 'dashboard' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span x-text="$t('admin.nav_dashboard')"></span>
        </a>
        <a href="/admin/users"
           class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium {{ $active === 'users' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm6 0h-3v-1a6 6 0 00-3-5.197 4 4 0 016 3.197V21z"/>
            </svg>
            <span x-text="$t('admin.nav_users')"></span>
        </a>
        <a href="/admin/messages"
           class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium {{ $active === 'messages' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span x-text="$t('admin.nav_messages')"></span>
        </a>
    </nav>
</aside>
