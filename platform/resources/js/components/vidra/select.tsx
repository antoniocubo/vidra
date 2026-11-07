import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Sheet, SheetClose, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { Check, ChevronDown, Search } from 'lucide-react';
import { useEffect, useId, useRef, useState, type ButtonHTMLAttributes, type KeyboardEvent, type ReactNode, type Ref } from 'react';
import { controlClasses, useFieldControl } from './form-field';

export type SelectOption<T extends string = string> = { value: T; label: string; description?: string; code?: string; disabled?: boolean };
export type SelectProps<T extends string = string> = Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'value' | 'onChange' | 'children' | 'name'> & {
    value: T | '';
    onChange: (value: T) => void;
    options: ReadonlyArray<SelectOption<T>>;
    placeholder?: string;
    name?: string;
    searchable?: boolean;
    searchPlaceholder?: string;
    footer?: ReactNode;
    ref?: Ref<HTMLButtonElement>;
};

const normalize = (text: string) =>
    text
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

function useMobileSelect() {
    const [mobile, setMobile] = useState(false);
    useEffect(() => {
        const media = window.matchMedia('(width < 640px)');
        const update = () => setMobile(media.matches);
        update();
        media.addEventListener('change', update);
        return () => media.removeEventListener('change', update);
    }, []);
    return mobile;
}

export function Select<T extends string = string>({
    value,
    onChange,
    options,
    placeholder = 'Selecione',
    name,
    searchable,
    searchPlaceholder = 'Buscar opção',
    footer,
    disabled,
    className,
    id,
    ref,
    onKeyDown,
    ...props
}: SelectProps<T>) {
    const field = useFieldControl();
    const uid = useId();
    const triggerId = id ?? field.id ?? `${uid}-controle`;
    const listId = `${uid}-lista`;
    const trigger = useRef<HTMLButtonElement>(null);
    const search = useRef<HTMLInputElement>(null);
    const list = useRef<HTMLUListElement>(null);
    const typeahead = useRef({ text: '', time: 0 });
    const tabbing = useRef(false);
    const mobile = useMobileSelect();
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [activeValue, setActiveValue] = useState<string>();
    const [label, setLabel] = useState(placeholder);
    const hasSearch = searchable === true || options.length > 8;
    const selected = value === '' ? undefined : options.find((option) => option.value === value);
    const filtered = options.filter((option) => normalize(`${option.code ?? ''} ${option.label}`).includes(normalize(query.trim())));
    const enabled = filtered.filter((option) => !option.disabled);
    const active = enabled.find((option) => option.value === activeValue) ?? enabled[0];
    const optionId = (option: SelectOption<T>) => `${uid}-opcao-${options.indexOf(option)}`;
    const activeId = active ? optionId(active) : undefined;

    useEffect(() => {
        if (open && activeId) document.getElementById(activeId)?.scrollIntoView({ block: 'nearest' });
    }, [open, activeId]);

    function changeOpen(next: boolean) {
        if (next && disabled) return;
        if (next) {
            setLabel(trigger.current?.labels?.[0]?.textContent?.trim() || props['aria-label'] || placeholder);
            setQuery('');
            setActiveValue(selected && !selected.disabled ? selected.value : options.find((option) => !option.disabled)?.value);
            tabbing.current = false;
        }
        setOpen(next);
    }

    function pick(option: SelectOption<T>) {
        if (disabled || option.disabled) return;
        onChange(option.value);
        changeOpen(false);
    }

    function navigate(event: KeyboardEvent<HTMLElement>) {
        if (event.nativeEvent.isComposing) return;
        const index = enabled.findIndex((option) => option.value === active?.value);
        let next: number;
        switch (event.key) {
            case 'ArrowDown':
                next = Math.min(index + 1, enabled.length - 1);
                break;
            case 'ArrowUp':
                next = Math.max(index - 1, 0);
                break;
            case 'Home':
                next = 0;
                break;
            case 'End':
                next = enabled.length - 1;
                break;
            case 'Enter':
                event.preventDefault();
                if (active) pick(active);
                return;
            case ' ':
                if (event.target === list.current) {
                    event.preventDefault();
                    if (active) pick(active);
                }
                return;
            default:
                return;
        }
        event.preventDefault();
        setActiveValue(enabled[next]?.value);
    }

    function triggerKeys(event: KeyboardEvent<HTMLButtonElement>) {
        onKeyDown?.(event);
        if (event.defaultPrevented || disabled || event.nativeEvent.isComposing) return;
        if (['Enter', ' ', 'ArrowDown', 'ArrowUp'].includes(event.key)) {
            event.preventDefault();
            changeOpen(true);
            return;
        }
        if (event.key.length !== 1 || event.ctrlKey || event.metaKey || event.altKey) return;
        event.preventDefault();
        const now = Date.now();
        const previous = now - typeahead.current.time < 500 ? typeahead.current.text : '';
        const text = previous + normalize(event.key);
        typeahead.current = { text, time: now };
        const prefix = [...text].every((letter) => letter === text[0]) ? text[0] : text;
        const start = options.findIndex((option) => option.value === value);
        const candidates = [...options.slice(start + 1), ...options.slice(0, start + 1)];
        const match = candidates.find(
            (option) => !option.disabled && (normalize(option.code ?? '').startsWith(prefix) || normalize(option.label).startsWith(prefix)),
        );
        if (match) onChange(match.value);
    }

    const focusContent = (event: Event) => {
        event.preventDefault();
        (hasSearch ? search.current : list.current)?.focus();
        if (activeId) document.getElementById(activeId)?.scrollIntoView({ block: 'nearest' });
    };
    const restoreFocus = (event: Event) => {
        event.preventDefault();
        if (!tabbing.current) trigger.current?.focus();
    };
    const contentKeys = (event: KeyboardEvent<HTMLDivElement>) => {
        if (event.key === 'Tab') {
            tabbing.current = true;
            trigger.current?.focus();
            changeOpen(false);
        }
    };
    const optionLabel = (option: SelectOption<T>) => (
        <>
            {option.code && (
                <>
                    <span className="font-mono">{option.code}</span>
                    <span aria-hidden="true"> · </span>
                </>
            )}
            {option.label}
        </>
    );
    const button = (
        <button
            {...props}
            ref={(node) => {
                trigger.current = node;
                if (typeof ref === 'function') return ref(node);
                if (ref) ref.current = node;
            }}
            id={triggerId}
            type="button"
            role="combobox"
            aria-haspopup="listbox"
            aria-expanded={open}
            aria-controls={open ? listId : undefined}
            aria-describedby={props['aria-describedby'] ?? field.describedBy}
            aria-invalid={props['aria-invalid'] ?? (field.invalid || undefined)}
            disabled={disabled}
            className={cn(
                controlClasses,
                'flex items-center justify-between gap-2 text-left',
                open && 'border-brand-500 ring-secondary ring-3',
                className,
            )}
            onKeyDown={triggerKeys}
        >
            <span className={cn('min-w-0 truncate', !selected && 'text-muted-foreground')}>{selected ? optionLabel(selected) : placeholder}</span>
            <ChevronDown aria-hidden="true" className={cn('text-muted-foreground size-[18px] shrink-0 transition-transform', open && 'rotate-180')} />
        </button>
    );
    const contents = (
        <>
            {hasSearch && (
                <div className="border-border flex shrink-0 items-center gap-2 border-b p-2">
                    <Search aria-hidden="true" className="text-muted-foreground ml-2 size-4 shrink-0" />
                    <input
                        ref={search}
                        value={query}
                        onChange={(event) => {
                            setQuery(event.target.value);
                            setActiveValue(undefined);
                        }}
                        aria-label={searchPlaceholder}
                        placeholder={searchPlaceholder}
                        role="combobox"
                        aria-haspopup="listbox"
                        aria-expanded={open}
                        aria-controls={listId}
                        aria-activedescendant={activeId}
                        aria-autocomplete="list"
                        onKeyDown={navigate}
                        className={cn(controlClasses, 'min-w-0 border-0 bg-transparent px-1')}
                    />
                </div>
            )}
            <ul
                ref={list}
                id={listId}
                role="listbox"
                aria-label={label}
                tabIndex={hasSearch ? -1 : 0}
                aria-activedescendant={!hasSearch ? activeId : undefined}
                onKeyDown={navigate}
                className="max-h-[296px] min-h-0 overflow-y-auto overscroll-contain p-1 outline-none"
            >
                {filtered.map((option) => (
                    <li
                        key={option.value}
                        id={optionId(option)}
                        role="option"
                        aria-selected={value === option.value && value !== ''}
                        aria-disabled={option.disabled || undefined}
                        onClick={() => pick(option)}
                        onPointerMove={() => {
                            if (!option.disabled) setActiveValue(option.value);
                        }}
                        onMouseDown={(event) => event.preventDefault()}
                        className={cn(
                            'flex min-h-10 items-center gap-3 rounded-md px-3 py-2 text-sm',
                            mobile && 'min-h-12',
                            option.disabled ? 'text-muted-foreground cursor-not-allowed opacity-50' : 'hover:bg-muted cursor-pointer',
                            active?.value === option.value && !option.disabled && 'bg-muted',
                            value === option.value && value !== '' && 'bg-secondary text-primary hover:bg-secondary font-semibold',
                        )}
                    >
                        <span className="min-w-0 flex-1">
                            <span>{optionLabel(option)}</span>
                            {option.description && <span className="text-muted-foreground block text-xs font-normal">{option.description}</span>}
                        </span>
                        {value === option.value && value !== '' && <Check aria-hidden="true" className="size-4 shrink-0" />}
                    </li>
                ))}
            </ul>
            {filtered.length === 0 && (
                <p role="status" className="text-muted-foreground px-4 py-3 text-sm">
                    Nenhuma opção com "{query}".
                </p>
            )}
            {footer && <div className="border-border shrink-0 border-t p-2">{footer}</div>}
        </>
    );

    return (
        <>
            {name && <input type="hidden" name={name} value={value} disabled={disabled} />}
            {mobile ? (
                <Sheet open={open && !disabled} onOpenChange={changeOpen}>
                    <SheetTrigger asChild>{button}</SheetTrigger>
                    <SheetContent
                        side="bottom"
                        aria-describedby={undefined}
                        onOpenAutoFocus={focusContent}
                        onCloseAutoFocus={restoreFocus}
                        onKeyDown={contentKeys}
                        className="bg-card flex max-h-[85dvh] flex-col gap-0 rounded-t-2xl p-0 pb-[env(safe-area-inset-bottom)] [&>button]:hidden"
                    >
                        <div className="flex shrink-0 items-center justify-between px-4 py-2">
                            <SheetTitle className="text-base">{label}</SheetTitle>
                            <SheetClose className="text-primary focus-visible:ring-ring h-11 rounded-md px-3 text-sm font-semibold focus-visible:ring-2">
                                Fechar
                            </SheetClose>
                        </div>
                        {contents}
                    </SheetContent>
                </Sheet>
            ) : (
                <Popover open={open && !disabled} onOpenChange={changeOpen}>
                    <PopoverTrigger asChild>{button}</PopoverTrigger>
                    <PopoverContent
                        onOpenAutoFocus={focusContent}
                        onCloseAutoFocus={restoreFocus}
                        onKeyDown={contentKeys}
                        className="flex max-h-[var(--radix-popover-content-available-height)] w-[var(--radix-popover-trigger-width)] flex-col overflow-hidden p-0"
                    >
                        {contents}
                    </PopoverContent>
                </Popover>
            )}
        </>
    );
}
