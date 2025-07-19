/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;
    readonly VITE_PUSHER_BEAMS_INSTANCE_ID: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
