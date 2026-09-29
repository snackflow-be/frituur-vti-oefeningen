/**
 * Kleine fetch-laag voor de JSON-API en de keuken-JSON-routes (02-architect A-1, A-2).
 * Altijd JSON, `Accept: application/json`, cookies mee (sessie voor /keuken), en
 * de XSRF-token uit de cookie als header voor schrijfverzoeken.
 */

export type ApiErrors = Record<string, string[]>;

export class ApiError extends Error {
    status: number;
    errors: ApiErrors;
    retryAfter: number | null;

    constructor(
        status: number,
        message: string,
        errors: ApiErrors = {},
        retryAfter: number | null = null,
    ) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
        this.retryAfter = retryAfter;
    }

    /** Eerste foutboodschap van een veld, of null. */
    fieldError(field: string): string | null {
        return this.errors[field]?.[0] ?? null;
    }
}

const FALLBACK_MESSAGES: Record<number, string> = {
    401: 'Hier moet je voor inloggen.',
    403: 'Dit mag je niet.',
    404: 'Niet gevonden.',
    422: 'Er klopt iets niet met wat je invulde.',
    429: 'Te veel bestellingen na elkaar. Probeer over een minuut opnieuw.',
};

function xsrfToken(): string | null {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : null;
}

type ErrorBody = {
    message?: string;
    errors?: ApiErrors;
    retry_after?: number;
};

async function request<T>(
    method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE',
    url: string,
    body?: unknown,
    signal?: AbortSignal,
): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    if (method !== 'GET') {
        const token = xsrfToken();

        if (token) {
            headers['X-XSRF-TOKEN'] = token;
        }
    }

    let response: Response;

    try {
        response = await fetch(url, {
            method,
            headers,
            credentials: 'same-origin',
            signal,
            ...(body === undefined ? {} : { body: JSON.stringify(body) }),
        });
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            throw error;
        }

        throw new ApiError(0, 'Geen verbinding. Controleer je internet.');
    }

    if (response.ok) {
        if (response.status === 204) {
            return undefined as T;
        }

        return (await response.json()) as T;
    }

    let data: ErrorBody = {};

    try {
        data = (await response.json()) as ErrorBody;
    } catch {
        // Geen JSON-body (bv. nginx-fout): val terug op de standaardtekst.
    }

    throw new ApiError(
        response.status,
        data.message ??
            FALLBACK_MESSAGES[response.status] ??
            'Er ging iets mis. Probeer opnieuw.',
        data.errors ?? {},
        data.retry_after ?? null,
    );
}

export function apiGet<T>(url: string, signal?: AbortSignal): Promise<T> {
    return request<T>('GET', url, undefined, signal);
}

export function apiPost<T>(url: string, body: unknown): Promise<T> {
    return request<T>('POST', url, body);
}

export function apiPatch<T>(url: string, body: unknown): Promise<T> {
    return request<T>('PATCH', url, body);
}

export function apiPut<T>(url: string, body: unknown): Promise<T> {
    return request<T>('PUT', url, body);
}

export function apiDelete<T>(url: string): Promise<T> {
    return request<T>('DELETE', url);
}
