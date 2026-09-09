export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToastType = 'success' | 'info' | 'warning' | 'error';

export type FlashToast = {
    type: FlashToastType | (string & {});
    message: string;
};
