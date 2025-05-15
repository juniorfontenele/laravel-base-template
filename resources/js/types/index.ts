import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: Flash;
    [key: string]: unknown;
    request_id: string;
    trace_id: string;
}

export interface Flash {
    message: string;
}

export interface User {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    timezone: string;
    locale: string;
    created_at: string;
    updated_at: string;
    created_by: string;
    updated_by: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Session {
    id: string;
    ip: string;
    user_agent: string;
    last_activity: string;
    self: boolean;
}
