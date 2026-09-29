import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate font-display text-base leading-tight font-extrabold tracking-tight uppercase">
                    Frituur VTI
                </span>
                <span className="truncate text-xs leading-tight text-muted-foreground">
                    Beheer
                </span>
            </div>
        </>
    );
}
