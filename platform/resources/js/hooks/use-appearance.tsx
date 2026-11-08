import { useEffect, useLayoutEffect, useState } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

/** Tema de quem ainda não escolheu nada em Configurações › Aparência. */
const temaPadrao: Appearance = 'light';

// Chave nova: a do starter kit ('appearance') guardava 'system' sem o usuário escolher, só por abrir a tela.
const chave = 'vidra-tema';

const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

const temaSalvo = (): Appearance => {
    const salvo = localStorage.getItem(chave);

    return salvo === 'light' || salvo === 'dark' || salvo === 'system' ? salvo : temaPadrao;
};

// Páginas públicas (como a landing) são a cara da marca: ficam claras mesmo com o sistema no escuro.
let claroForcado = false;

const applyTheme = (appearance: Appearance) => {
    const isDark = !claroForcado && (appearance === 'dark' || (appearance === 'system' && prefersDark()));

    document.documentElement.classList.toggle('dark', isDark);
};

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

const handleSystemThemeChange = () => applyTheme(temaSalvo());

export function initializeTheme() {
    localStorage.removeItem('appearance');
    applyTheme(temaSalvo());
    mediaQuery.addEventListener('change', handleSystemThemeChange);
}

/** Mantém a página no tema claro enquanto estiver montada e devolve o tema escolhido ao sair. */
export function useTemaClaro() {
    useLayoutEffect(() => {
        claroForcado = true;
        handleSystemThemeChange();

        return () => {
            claroForcado = false;
            handleSystemThemeChange();
        };
    }, []);
}

export function useAppearance() {
    const [appearance, setAppearance] = useState<Appearance>(temaPadrao);

    // Só grava quando o usuário escolhe; abrir a tela de aparência não muda nada.
    const updateAppearance = (mode: Appearance) => {
        setAppearance(mode);
        localStorage.setItem(chave, mode);
        applyTheme(mode);
    };

    useEffect(() => setAppearance(temaSalvo()), []);

    return { appearance, updateAppearance };
}
