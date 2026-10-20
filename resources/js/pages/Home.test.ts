import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Home from './Home.vue';

describe('Home', () => {
    it('mostra o painel vazio com o título acessível', () => {
        const wrapper = mount(Home);
        expect(wrapper.find('main').exists()).toBe(true);
        expect(wrapper.find('h1').text()).toBe('ZapVault');
    });
});
