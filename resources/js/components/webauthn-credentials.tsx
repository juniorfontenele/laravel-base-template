import { router, useForm } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { Button } from '@/components/ui/button';

import HeadingSmall from '@/components/heading-small';

import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { WebAuthnCredential } from '@/types';
import Webpass from '@laragear/webpass';
import { FormEventHandler, useState } from 'react';
import { WebAuthnCredentialItem } from './auth/webauthn-credential-item';

export default function WebAuthnCredentials({ credentials }: { credentials: WebAuthnCredential[] }) {
    const { t } = useLaravelReactI18n();
    const { delete: destroy, processing, reset, clearErrors } = useForm();

    const [dialogOpen, setDialogOpen] = useState(false);

    const deleteAllPasskeys: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('webauthn.credentials.destroy-all'), {
            preserveScroll: true,
            onFinish: () => closeModal(),
        });
    };

    const deletePasskey = (credential: WebAuthnCredential) => {
        destroy(route('webauthn.credentials.destroy', credential.id), {
            preserveScroll: true,
        });
    };

    const closeModal = () => {
        setDialogOpen(false);
        clearErrors();
        reset();
    };

    const registerWebAuthn = async () => {
        const { success, error } = await Webpass.attest(route('webauthn.register.options'), route('webauthn.register'));

        if (success) {
            return router.reload();
        }
    };

    return (
        <div className="space-y-6">
            <HeadingSmall title={t('Chaves')} description={t('Gerencie suas chaves de acesso')} />

            <div className="flex items-center gap-4">
                <Button disabled={processing} onClick={registerWebAuthn}>
                    {t('Registrar nova chave')}
                </Button>
            </div>

            {Array.isArray(credentials) ? (
                credentials.map((credential) => (
                    <WebAuthnCredentialItem
                        key={credential.id}
                        credential={credential}
                        onDelete={() => {
                            deletePasskey(credential);
                        }}
                        processing={processing}
                    />
                ))
            ) : (
                <p className="text-gray-500">{t('Nenhuma chave encontrada.')}</p>
            )}

            {credentials.length > 1 && (
                <Dialog open={dialogOpen}>
                    <DialogTrigger asChild>
                        <Button variant="destructive" onClick={() => setDialogOpen(true)}>
                            {t('Excluir todas as chaves')}
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>{t('Tem certeza que deseja excluir todas as chaves?')}</DialogTitle>
                        <DialogDescription>{t('Depois de excluir todas as chaves, você não poderá mais usá-las para entrar.')}</DialogDescription>
                        <form className="space-y-6" onSubmit={deleteAllPasskeys}>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" onClick={closeModal}>
                                        {t('Cancelar')}
                                    </Button>
                                </DialogClose>

                                <Button variant="destructive" disabled={processing} asChild>
                                    <button type="submit">{t('Excluir todas as chaves')}</button>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            )}
        </div>
    );
}
