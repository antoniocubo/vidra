import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'react-toastify';

/** Ação opcional dentro do aviso (ex.: "Imprimir etiquetas"). */
export type AcaoDoAviso = { label: string; onClick: () => void };

// Props já avisadas: o StrictMode roda o efeito duas vezes com o mesmo objeto.
const avisadas = new WeakSet<object>();

function readFlashSuccess(props: unknown): string | null {
    if (typeof props !== 'object' || props === null || !('flash' in props)) return null;
    const flash = props.flash;
    if (typeof flash !== 'object' || flash === null || !('success' in flash)) return null;
    return typeof flash.success === 'string' && flash.success !== '' ? flash.success : null;
}

/**
 * Mostra `flash.success` num aviso do react-toastify (o ToastContainer fica em app.tsx).
 * Reaparece a cada nova resposta, mesmo com o mesmo texto. Devolve a mensagem para quem
 * também quer usá-la na tela (ex.: o campo do leitor de amostras).
 */
export function useFlashToast(acao?: AcaoDoAviso): { message: string | null } {
    const { props } = usePage();
    const message = readFlashSuccess(props);
    const acaoAtual = useRef(acao);
    acaoAtual.current = acao;

    useEffect(() => {
        if (message === null || avisadas.has(props)) return;
        avisadas.add(props);
        const extra = acaoAtual.current;
        toast.success(
            extra ? (
                <span className="flex flex-wrap items-center gap-3">
                    <span>{message}</span>
                    <button
                        type="button"
                        onClick={extra.onClick}
                        className="bg-background/15 hover:bg-background/25 focus-visible:outline-brand-500 h-11 rounded-md px-3 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        {extra.label}
                    </button>
                </span>
            ) : (
                message
            ),
        );
    }, [props, message]);

    return { message };
}
