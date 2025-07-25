import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';

export default function SettingsLayout({ children }: { children: React.ReactNode }) {
    const { t } = useLaravelReactI18n();

    const sidebarNavItems: NavItem[] = [
        {
            title: t('Perfil'),
            url: '/settings/profile',
            icon: null,
        },
        {
            title: t('Senha'),
            url: '/settings/password',
            icon: null,
        },
        {
            title: t('Aparência'),
            url: '/settings/appearance',
            icon: null,
        },
    ];

    const currentPath = window.location.pathname;

    return (
        <div className="px-4 py-6">
            <Heading title={t('Configurações')} description={t('Gerencie seu perfil e configurações da conta')} />

            <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {sidebarNavItems.map((item) => (
                            <Button
                                key={item.url}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': currentPath === item.url,
                                })}
                            >
                                <Link href={item.url} prefetch>
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 md:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">{children}</section>
                </div>
            </div>
        </div>
    );
}
