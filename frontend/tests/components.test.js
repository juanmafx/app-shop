import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

describe('InputError', () => {
    it('renders the message', () => {
        const wrapper = mount(InputError, { props: { message: 'Required field' } });
        expect(wrapper.text()).toBe('Required field');
        expect(wrapper.find('p').isVisible()).toBe(true);
    });

    it('escapes markup in messages', () => {
        const wrapper = mount(InputError, { props: { message: '<img src=x onerror=alert(1)>' } });
        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.text()).toContain('<img');
    });
});

describe('InputLabel', () => {
    it('renders the value prop', () => {
        expect(mount(InputLabel, { props: { value: 'Email' } }).text()).toBe('Email');
    });

    it('prefers slot content when no value is given', () => {
        expect(mount(InputLabel, { slots: { default: 'Password' } }).text()).toBe('Password');
    });
});

describe('PrimaryButton', () => {
    it('renders slot content', () => {
        expect(mount(PrimaryButton, { slots: { default: 'Save' } }).text()).toBe('Save');
    });
});

describe('TextInput', () => {
    it('shows the bound value', () => {
        const wrapper = mount(TextInput, { props: { modelValue: 'initial' } });
        expect(wrapper.find('input').element.value).toBe('initial');
    });

    it('emits updates on input', async () => {
        const wrapper = mount(TextInput, { props: { modelValue: '' } });
        await wrapper.find('input').setValue('typed');
        expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['typed']);
    });
});

describe('Checkbox', () => {
    it('emits the checked state', async () => {
        const wrapper = mount(Checkbox, { props: { checked: false } });
        await wrapper.find('input').setValue(true);
        expect(wrapper.emitted('update:checked').at(-1)).toEqual([true]);
    });
});
