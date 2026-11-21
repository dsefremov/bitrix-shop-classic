import { createElement, type ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import './styles/app.css';
import AddToCart from './components/AddToCart';
import CartQuantity from './components/CartQuantity';
import ConfirmSubmit from './components/ConfirmSubmit';
import FilterPanel from './components/FilterPanel';
import MobileMenu from './components/MobileMenu';
import SearchBox from './components/SearchBox';
import SortSelect from './components/SortSelect';

// Шаблоны компонентов Битрикса выводят <div data-react-component="Имя" data-props='{...}'>
// (Dsefremov\ShopClassic\View\Html::react), загрузчик монтирует в них React-компоненты.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const registry: Record<string, ComponentType<any>> = {
    AddToCart,
    CartQuantity,
    ConfirmSubmit,
    FilterPanel,
    MobileMenu,
    SearchBox,
    SortSelect,
};

export function mountAll(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-react-component]').forEach((element) => {
        if (element.dataset.reactMounted === 'true') {
            return;
        }
        const name = element.dataset.reactComponent ?? '';
        const component = registry[name];
        if (!component) {
            console.error(`Неизвестный React-компонент: ${name}`);
            return;
        }
        const props: unknown = JSON.parse(element.dataset.props ?? '{}');
        element.dataset.reactMounted = 'true';
        createRoot(element).render(createElement(component, props as object));
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => mountAll());
} else {
    mountAll();
}
