import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue');

    return {
        Link: { render: () => null },
        usePage: () => ({
            props: { auth: { user: { name: 'Test User', email: 'user@example.test', email_verified_at: null } } },
        }),
        useForm: (fields) =>
            reactive({ ...fields, errors: {}, processing: false, recentlySuccessful: false, patch: vi.fn(), reset: vi.fn() }),
    };
});

const mountForm = () => mount(UpdateProfileInformationForm, { global: { mocks: { route: () => '/profile' } } });

describe('UpdateProfileInformationForm', () => {
    it('hides the current password field while the email is unchanged', () => {
        expect(mountForm().find('#profile_current_password').exists()).toBe(false);
    });

    it('asks for the current password when the email changes', async () => {
        const wrapper = mountForm();
        await wrapper.find('#email').setValue('new@example.test');
        expect(wrapper.find('#profile_current_password').exists()).toBe(true);
    });
});
