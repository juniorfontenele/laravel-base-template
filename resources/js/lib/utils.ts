import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Detecta o sistema operacional a partir do user-agent do cliente
 * @return {string} - Nome do sistema operacional detectado
 */
export function detectOS(): string {
    const ua = navigator.userAgent.toLowerCase();

    if (ua.indexOf('windows nt') !== -1) {
        const version = ua.match(/windows nt (\d+\.\d+)/);
        const versionMap = {
            '10.0': 'Windows 10',
            '6.3': 'Windows 8.1',
            '6.2': 'Windows 8',
            '6.1': 'Windows 7',
            '6.0': 'Windows Vista',
            '5.2': 'Windows XP x64',
            '5.1': 'Windows XP',
        };
        return version ? versionMap[version[1]] || 'Windows' : 'Windows';
    }

    if (ua.indexOf('mac os x') !== -1) {
        const version = ua.match(/mac os x (\d+[._]\d+)/);
        return version ? `macOS ${version[1].replace('_', '.')}` : 'macOS';
    }

    if (ua.indexOf('android') !== -1) return 'Android';
    if (ua.indexOf('ios') !== -1 || ua.indexOf('iphone') !== -1 || ua.indexOf('ipad') !== -1) return 'iOS';
    if (ua.indexOf('linux') !== -1) return 'Linux';

    return 'Sistema Desconhecido';
}

export interface Browser {
    name: string;
    version: string;
}

/**
 * Detecta o navegador a partir do user-agent do cliente
 * @return {Browser} - Objeto contendo nome e versão do navegador
 */
export function detectBrowser() {
    const ua = navigator.userAgent;
    let browserName = 'Navegador desconhecido';
    let version = '';

    // Chrome
    if (/Chrome/.test(ua) && !/Chromium|Edge|Edg|OPR|Opera/.test(ua)) {
        browserName = 'Chrome';
        version = ua.match(/Chrome\/(\d+\.\d+)/);
    }
    // Firefox
    else if (/Firefox/.test(ua)) {
        browserName = 'Firefox';
        version = ua.match(/Firefox\/(\d+\.\d+)/);
    }
    // Safari
    else if (/Safari/.test(ua) && !/Chrome/.test(ua)) {
        browserName = 'Safari';
        version = ua.match(/Version\/(\d+\.\d+)/);
    }
    // Edge (novo)
    else if (/Edg/.test(ua)) {
        browserName = 'Edge';
        version = ua.match(/Edg\/(\d+\.\d+)/);
    }
    // Opera
    else if (/OPR|Opera/.test(ua)) {
        browserName = 'Opera';
        version = ua.match(/(?:OPR|Opera)\/(\d+\.\d+)/);
    }

    return {
        name: browserName,
        version: version ? version[1] : 'Desconhecido',
    };
}
