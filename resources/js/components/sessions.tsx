import { useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { Button } from '@/components/ui/button';

import HeadingSmall from '@/components/heading-small';

import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Session } from '@/types';
import { FormEventHandler, useState } from 'react';
import { SessionItem } from './session/session-item';

export default function Sessions({ sessions }: { sessions: Session[] }) {
    const { delete: destroy, processing, reset, clearErrors } = useForm();
    const { t } = useLaravelReactI18n();

    const [dialogOpen, setDialogOpen] = useState(false);

    const destroyOtherSessions: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('session.destroy-others'), {
            preserveScroll: true,
            onFinish: () => closeModal(),
        });
    };

    const destroySession = (session: Session) => {
        destroy(route('session.destroy', session.id), {
            preserveScroll: true,
        });
    };

    const closeModal = () => {
        setDialogOpen(false);
        clearErrors();
        reset();
    };

    return (
        <div className="space-y-6">
            <HeadingSmall title={t('Sessões')} description={t('Gerencie suas sessões')} />

            {Array.isArray(sessions) ? (
                sessions.map((session) => (
                    <SessionItem
                        key={session.id}
                        session={session}
                        onDelete={() => {
                            destroySession(session);
                        }}
                        processing={processing}
                    />
                ))
            ) : (
                <p className="text-gray-500">{t('Nenhuma sessão ativa encontrada.')}</p>
            )}

            {sessions.length > 1 && (
                <Dialog open={dialogOpen}>
                    <DialogTrigger asChild>
                        <Button variant="destructive" onClick={() => setDialogOpen(true)}>
                            {t('Desconectar todas as sessões')}
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>{t('Tem certeza que deseja desconectar todas as outras sessões?')}</DialogTitle>
                        <DialogDescription>{t('Ao desconectar todas as outras sessões, você será desconectado delas.')}</DialogDescription>
                        <form className="space-y-6" onSubmit={destroyOtherSessions}>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" onClick={closeModal}>
                                        {t('Cancelar')}
                                    </Button>
                                </DialogClose>

                                <Button variant="destructive" disabled={processing} asChild>
                                    <button type="submit">{t('Desconectar todas as sessões')}</button>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            )}
        </div>
    );
}
