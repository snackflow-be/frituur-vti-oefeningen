import { useCallback, useRef, useState } from 'react';

const STORAGE_KEY = 'frituur.keuken.sound';

function readStored(): boolean {
    try {
        return localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

/**
 * Korte tweetonige piep via AudioContext (geen geluidsbestand). Browsers laten geluid pas toe
 * na een klik, dus de schakelaar in de kop maakt de context aan; de keuze blijft bewaard.
 */
export function useKitchenSound() {
    const [enabled, setEnabled] = useState<boolean>(readStored);
    const contextRef = useRef<AudioContext | null>(null);

    const ensureContext = useCallback((): AudioContext | null => {
        if (typeof window === 'undefined' || !('AudioContext' in window)) {
            return null;
        }

        contextRef.current ??= new AudioContext();

        if (contextRef.current.state === 'suspended') {
            void contextRef.current.resume();
        }

        return contextRef.current;
    }, []);

    const play = useCallback(() => {
        if (!enabled) {
            return;
        }

        const context = ensureContext();

        if (!context) {
            return;
        }

        const start = context.currentTime;

        [880, 1175].forEach((frequency, index) => {
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = 'square';
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0.0001, start + index * 0.18);
            gain.gain.exponentialRampToValueAtTime(
                0.25,
                start + index * 0.18 + 0.02,
            );
            gain.gain.exponentialRampToValueAtTime(
                0.0001,
                start + index * 0.18 + 0.16,
            );
            oscillator.connect(gain).connect(context.destination);
            oscillator.start(start + index * 0.18);
            oscillator.stop(start + index * 0.18 + 0.18);
        });
    }, [enabled, ensureContext]);

    const toggle = useCallback(() => {
        const next = !enabled;
        setEnabled(next);

        try {
            localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
        } catch {
            // Privémodus of geblokkeerde opslag: dan onthouden we het niet.
        }

        if (next) {
            ensureContext();
        }
    }, [enabled, ensureContext]);

    return { enabled, toggle, play };
}
