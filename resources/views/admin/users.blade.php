@extends('layouts.app')

@php
    use App\Enums\Role;
    use App\Enums\UserStatus;
@endphp

@section('content')
    <div x-data="adminUsersPage()" x-init="init()" class="flex w-full flex-col gap-6 sm:flex-row" x-cloak>
        @include('partials.admin-sidebar', ['active' => 'users'])

        <div class="min-w-0 flex-1 space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-900" x-text="$t('admin.users_title')"></h1>
                    <p class="mt-1 text-sm text-gray-500" x-text="$t('admin.users_subtitle')"></p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <form @submit.prevent="applySearch()" class="sm:w-64">
                        <input type="search" x-model="search" @search="applySearch()"
                               :placeholder="$t('admin.search_users')"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                    </form>
                    <select x-model="role" @change="applyFilters()"
                            class="rounded-md border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" x-text="$t('admin.filter_all_roles')"></option>
                        @foreach (Role::cases() as $role)
                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    <select x-model="status" @change="applyFilters()"
                            class="rounded-md border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" x-text="$t('admin.filter_all_statuses')"></option>
                        @foreach (UserStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-gray-900/5">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50">
                        <tr class="text-gray-500">
                            <th class="px-4 py-3 text-start font-medium" x-text="$t('admin.col_name')"></th>
                            <th class="px-4 py-3 text-start font-medium" x-text="$t('admin.col_email')"></th>
                            <th class="px-4 py-3 text-start font-medium" x-text="$t('admin.col_role')"></th>
                            <th class="px-4 py-3 text-start font-medium" x-text="$t('admin.col_status')"></th>
                            <th class="px-4 py-3 text-start font-medium" x-text="$t('admin.col_verification')"></th>
                            <th class="px-4 py-3 text-start font-medium" x-text="$t('admin.col_joined')"></th>
                            <th class="px-4 py-3 text-end font-medium" x-text="$t('admin.col_actions')"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loaded && users.length === 0">
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400" x-text="$t('admin.no_users')"></td></tr>
                        </template>

                        <template x-for="user in users" :key="user.id">
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900" x-text="user.name"></span>
                                    <span x-show="isSelf(user)" class="ms-1 text-xs text-gray-400" x-text="`(${$t('admin.you')})`"></span>
                                </td>
                                <td class="px-4 py-3 text-gray-600" x-text="user.email"></td>
                                <td class="px-4 py-3">
                                    <select :value="user.role" @change="updateRole(user, $event.target.value)"
                                            :disabled="isSelf(user)"
                                            class="rounded-md border-gray-300 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50">
                                        @foreach (Role::cases() as $role)
                                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <select :value="user.status" @change="updateStatus(user, $event.target.value)"
                                            class="rounded-md border-gray-300 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach (UserStatus::cases() as $status)
                                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                          :class="user.email_verified_at ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                          x-text="user.email_verified_at ? $t('admin.status_verified') : $t('admin.status_unverified')"></span>
                                </td>
                                <td class="px-4 py-3 text-gray-600" x-text="user.created_at ? new Date(user.created_at).toLocaleDateString() : ''"></td>
                                <td class="px-4 py-3 text-end">
                                    <div class="flex items-center justify-end gap-2">
                                        <a :href="`/admin/users/${user.id}`" :title="$t('admin.view_details')" :aria-label="$t('admin.view_details')"
                                           class="inline-flex rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-indigo-600">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </a>
                                        <button x-show="!isSelf(user)" @click="destroy(user)" :title="$t('common.delete')" :aria-label="$t('common.delete')"
                                                class="inline-flex rounded-md p-1.5 text-gray-500 hover:bg-red-50 hover:text-red-600">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div x-show="meta && meta.last_page > 1" class="flex items-center justify-between text-sm">
                <p class="text-gray-500">
                    <span x-text="meta?.from ?? 0"></span>–<span x-text="meta?.to ?? 0"></span>
                    <span x-text="$t('admin.of')"></span>
                    <span x-text="meta?.total ?? 0"></span>
                </p>
                <div class="flex gap-2">
                    <button @click="goTo(page - 1)" :disabled="page <= 1"
                            class="rounded-md px-3 py-1.5 font-medium text-gray-600 ring-1 ring-gray-300 hover:bg-gray-50 disabled:opacity-40"
                            x-text="$t('admin.prev')"></button>
                    <button @click="goTo(page + 1)" :disabled="meta && page >= meta.last_page"
                            class="rounded-md px-3 py-1.5 font-medium text-gray-600 ring-1 ring-gray-300 hover:bg-gray-50 disabled:opacity-40"
                            x-text="$t('admin.next')"></button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function adminUsersPage() {
            return {
                users: [],
                meta: null,
                search: '',
                role: '',
                status: '',
                page: 1,
                loaded: false,
                init() {
                    this.$watch('$store.auth.ready', () => this.guard());
                    this.$watch('$store.auth.user', () => this.guard());
                    this.guard();
                },
                guard() {
                    if (!window.adminGuard(this)) return;
                    if (!this.loaded) this.load();
                },
                async load() {
                    this.loaded = true;
                    await this.fetchUsers();
                },
                async fetchUsers() {
                    try {
                        const params = new URLSearchParams({ page: this.page });
                        if (this.search) params.set('search', this.search);
                        if (this.role) params.set('role', this.role);
                        if (this.status) params.set('status', this.status);
                        const data = await window.auth.fetchJson(`/api/admin/users?${params.toString()}`);
                        this.users = data.data;
                        this.meta = data.meta;
                    } catch (e) {
                        if (e.status === 403) window.location.href = '/dashboard';
                    }
                },
                applySearch() {
                    this.page = 1;
                    this.fetchUsers();
                },
                applyFilters() {
                    this.page = 1;
                    this.fetchUsers();
                },
                goTo(page) {
                    if (page < 1 || (this.meta && page > this.meta.last_page)) return;
                    this.page = page;
                    this.fetchUsers();
                },
                isSelf(user) {
                    return user.id === this.$store.auth.user?.id;
                },
                async updateRole(user, role) {
                    if (role === user.role) return;
                    try {
                        const data = await window.auth.fetchJson(`/api/admin/users/${user.id}`, {
                            method: 'PATCH',
                            body: JSON.stringify({ role }),
                        });
                        const i = this.users.findIndex(u => u.id === user.id);
                        if (i > -1) this.users[i] = data.data;
                        window.dispatchEvent(new CustomEvent('app:toast', {
                            detail: { message: this.$t('admin.user_updated'), type: 'success' },
                        }));
                    } catch (e) {
                        // Roll the select back to the stored value on failure.
                        this.fetchUsers();
                    }
                },
                async updateStatus(user, status) {
                    if (status === user.status) return;
                    try {
                        const data = await window.auth.fetchJson(`/api/admin/users/${user.id}`, {
                            method: 'PATCH',
                            body: JSON.stringify({ status }),
                        });
                        const i = this.users.findIndex(u => u.id === user.id);
                        if (i > -1) this.users[i] = data.data;
                        window.dispatchEvent(new CustomEvent('app:toast', {
                            detail: { message: this.$t('admin.status_updated'), type: 'success' },
                        }));
                    } catch (e) {
                        // Roll the select back to the stored value on failure.
                        this.fetchUsers();
                    }
                },
                async destroy(user) {
                    if (!await this.$store.confirm.ask(this.$t('admin.confirm_delete_user'))) return;
                    try {
                        await window.auth.fetchJson(`/api/admin/users/${user.id}`, { method: 'DELETE' });
                        this.users = this.users.filter(u => u.id !== user.id);
                        window.dispatchEvent(new CustomEvent('app:toast', {
                            detail: { message: this.$t('admin.user_deleted'), type: 'success' },
                        }));
                    } catch (e) { /* fetchJson already surfaced the error */ }
                },
            };
        }
    </script>
@endpush
