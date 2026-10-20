import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { handleEscape } from '../../lib/escape';
import ChatMenu from './ChatMenu.vue';

describe('ChatMenu', () => {
    it('abre com Renomear e Excluir e avisa a escolha', async () => {
        const w = mount(ChatMenu, { attachTo: document.body });
        const button = w.get('button[aria-haspopup="menu"]');

        await button.trigger('click');
        expect(button.attributes('aria-expanded')).toBe('true');
        const items = w.findAll('[role="menuitem"]');
        expect(items.map((i) => i.text())).toEqual(['Renomear conversa', 'Excluir conversa']);

        await items[1].trigger('click');
        expect(w.emitted('remove')).toHaveLength(1);
        expect(w.find('[role="menu"]').exists()).toBe(false);
        w.unmount();
    });

    it('Esc fecha o menu', async () => {
        const w = mount(ChatMenu, { attachTo: document.body });
        await w.get('button').trigger('click');

        expect(handleEscape()).toBe(true);
        await w.vm.$nextTick();
        expect(w.find('[role="menu"]').exists()).toBe(false);
        expect(handleEscape()).toBe(false);
        w.unmount();
    });
});
