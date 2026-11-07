import { cn } from '@/lib/utils';
import { Slot, Slottable } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { LoaderCircle, type LucideIcon } from 'lucide-react';
import { forwardRef, type ButtonHTMLAttributes } from 'react';

export const buttonVariants = cva(
    'inline-flex shrink-0 items-center justify-center gap-2 rounded-md border font-semibold whitespace-nowrap transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 disabled:cursor-not-allowed disabled:opacity-45 [&_svg]:pointer-events-none [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                primary: 'border-primary bg-primary text-primary-foreground hover:bg-primary/90',
                secondary: 'border-input bg-card text-foreground hover:border-muted-foreground hover:bg-background',
                ghost: 'border-transparent bg-transparent text-brand-500 hover:bg-accent hover:text-accent-foreground',
                danger: 'border-crit-600 bg-crit-600 text-white hover:bg-crit-600/90',
            },
            size: {
                sm: 'h-9 px-3.5 text-[13px] [&_svg]:size-4',
                md: 'h-11 px-5 text-sm [&_svg]:size-[18px]',
                lg: 'h-13 px-6 text-[15px] [&_svg]:size-[18px]',
            },
        },
        defaultVariants: { variant: 'primary', size: 'md' },
    },
);

export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> &
    VariantProps<typeof buttonVariants> & {
        /** Ícone à esquerda do texto. */
        icon?: LucideIcon;
        /** Mostra o spinner e desabilita o botão, mantendo a largura. */
        loading?: boolean;
        /** Renderiza o filho (ex.: <Link> do Inertia) com o visual do botão. */
        asChild?: boolean;
    };

/**
 * Botão do design system. Um único `primary` por área de ação, à direita.
 * O texto diz a ação com o objeto: "Liberar laudo", nunca "OK".
 */
export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
    ({ className, variant, size, icon: Icon, loading = false, asChild = false, disabled, children, type, ...props }, ref) => {
        const Comp = asChild ? Slot : 'button';
        const LeadingIcon = loading ? LoaderCircle : Icon;

        return (
            <Comp
                ref={ref}
                type={asChild ? undefined : (type ?? 'button')}
                className={cn(buttonVariants({ variant, size }), className)}
                disabled={asChild ? undefined : disabled || loading}
                aria-busy={loading || undefined}
                {...props}
            >
                {LeadingIcon && <LeadingIcon aria-hidden="true" className={cn(loading && 'animate-spin')} />}
                <Slottable>{children}</Slottable>
            </Comp>
        );
    },
);
Button.displayName = 'Button';

export type IconButtonProps = Omit<ButtonProps, 'children' | 'icon'> & {
    icon: LucideIcon;
    /** Obrigatório: é o que o leitor de tela anuncia. */
    label: string;
};

/** Botão só com ícone. Mantém a área de toque quadrada do tamanho do botão. */
export const IconButton = forwardRef<HTMLButtonElement, IconButtonProps>(({ icon: Icon, label, size = 'md', className, ...props }, ref) => (
    <Button
        ref={ref}
        size={size}
        aria-label={label}
        title={label}
        className={cn('px-0', { sm: 'w-9', md: 'w-11', lg: 'w-13' }[size ?? 'md'], className)}
        {...props}
    >
        <Icon aria-hidden="true" />
    </Button>
));
IconButton.displayName = 'IconButton';
