import { Form, Head } from '@inertiajs/react';
import { BigButton, Field } from '@/components/frituur';
import PasskeyVerify from '@/components/passkey-verify';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/login';

type Props = {
    status?: string;
};

/** Login voor personeel: e-mail + wachtwoord, geen registratie of "wachtwoord vergeten" (A-9). */
export default function Login({ status }: Props) {
    return (
        <>
            <Head title="Inloggen" />

            <PasskeyVerify />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <Field
                            label="E-mailadres"
                            type="email"
                            name="email"
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="email"
                            inputMode="email"
                            placeholder="naam@frituurvti.be"
                            error={errors.email}
                        />

                        <Field
                            label="Wachtwoord"
                            type="password"
                            name="password"
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            placeholder="Wachtwoord"
                            error={errors.password}
                        />

                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                                className="size-6"
                            />
                            <Label htmlFor="remember" className="text-base">
                                Aangemeld blijven op dit toestel
                            </Label>
                        </div>

                        <BigButton
                            type="submit"
                            block
                            tabIndex={4}
                            loading={processing}
                            data-test="login-button"
                        >
                            Inloggen
                        </BigButton>

                        <p className="text-center text-sm text-muted-foreground">
                            Geen toegang? Vraag de baas om je toe te voegen als
                            personeel.
                        </p>
                    </>
                )}
            </Form>

            {status && (
                <p
                    role="status"
                    className="mt-4 text-center text-sm font-bold text-status-ready"
                >
                    {status}
                </p>
            )}
        </>
    );
}

Login.layout = {
    title: 'Personeel',
    description: 'Log in om de keuken en het beheer te openen.',
};
