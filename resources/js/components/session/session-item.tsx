import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Session } from '@/types';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Trash2 } from 'lucide-react';
import React, { FormEventHandler } from 'react';

const getBrowserInfo = (userAgent: string): string => {
    const { t } = useLaravelReactI18n();
    if (userAgent.includes('Chrome')) return 'Chrome';
    if (userAgent.includes('Firefox')) return 'Firefox';
    if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) return 'Safari';
    if (userAgent.includes('Edge')) return 'Edge';
    if (userAgent.includes('Opera') || userAgent.includes('OPR')) return 'Opera';
    return t('Navegador desconhecido');
};

export const SessionItem: React.FC<{
    session: Session;
    onDelete: () => void;
    processing: boolean;
}> = ({ session, onDelete, processing }) => {
    const { t } = useLaravelReactI18n();
    const browserName = getBrowserInfo(session.user_agent);

    const onSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        onDelete();
    };

    return (
        <Card className="mb-4">
            <CardContent className="p-4">
                <div className="flex items-center justify-between">
                    <div className="flex flex-col">
                        <div className="mb-1 flex items-center space-x-2">
                            <span className="font-medium">IP: {session.ip}</span>
                            {session.self && (
                                <Badge variant="secondary" className="ml-2">
                                    {t('Sessão atual')}
                                </Badge>
                            )}
                        </div>
                        <p className="text-sm text-gray-500">
                            {t('Navegador')}: {browserName}
                        </p>
                        <p className="text-sm text-gray-500">
                            {t('Última atividade')}: {session.last_activity}
                        </p>
                    </div>

                    {!session.self && (
                        <div>
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button variant="ghost" size="icon" className="text-gray-500 hover:text-red-500">
                                                    <Trash2 size={18} />
                                                </Button>
                                            </DialogTrigger>
                                            <DialogContent>
                                                <DialogTitle>{t('Tem certeza que deseja destruir esta sessão?')}</DialogTitle>
                                                <DialogDescription>{t('Ao destruir esta sessão, você será desconectado dela.')}</DialogDescription>
                                                <form className="space-y-6" onSubmit={onSubmit}>
                                                    <DialogFooter className="gap-2">
                                                        <DialogClose asChild>
                                                            <Button variant="secondary">{t('Cancelar')}</Button>
                                                        </DialogClose>

                                                        <Button variant="destructive" disabled={processing} asChild>
                                                            <button type="submit">{t('Destruir sessão')}</button>
                                                        </Button>
                                                    </DialogFooter>
                                                </form>
                                            </DialogContent>
                                        </Dialog>
                                    </TooltipTrigger>
                                    <TooltipContent>{t('Excluir sessão')}</TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
};

// Componente principal
// export const SessionManager: React.FC<SessionManagerProps> = ({
//   sessions,
//   onDeleteSession
// }) => {
//   return (
//     <div className="space-y-4">
//       <h2 className="text-xl font-semibold mb-4">Gerenciamento de Sessões</h2>

//       {sessions.length === 0 ? (
//         <p className="text-gray-500">Nenhuma sessão ativa encontrada.</p>
//       ) : (
//         <div>
//           {sessions.map((session, index) => (
//             <SessionItem
//               key={`${session.ip}-${index}`}
//               session={session}
//               onDelete={() => onDeleteSession(session.ip)}
//             />
//           ))}
//         </div>
//       )}
//     </div>
//   );
// };

// Exemplo de uso:
/*
import { SessionManager } from './components/SessionManager';

const App = () => {
  const [sessions, setSessions] = useState<Session[]>([]);
  
  useEffect(() => {
    // Fetch sessions from API
    fetchSessions().then(data => setSessions(data));
  }, []);
  
  const handleDeleteSession = (ip: string) => {
    // Call API to delete session
    deleteSession(ip).then(() => {
      setSessions(sessions.filter(session => session.ip !== ip));
    });
  };
  
  return (
    <div className="container mx-auto p-4">
      <SessionManager 
        sessions={sessions} 
        onDeleteSession={handleDeleteSession} 
      />
    </div>
  );
};
*/
