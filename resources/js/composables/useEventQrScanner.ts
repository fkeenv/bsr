import { onBeforeUnmount, onMounted, ref } from 'vue';

type QrDetector = {
    detect(image: HTMLVideoElement): Promise<{ rawValue: string }[]>;
};
type QrDetectorConstructor = {
    new (options: { formats: string[] }): QrDetector;
    getSupportedFormats(): Promise<string[]>;
};

export function useEventQrScanner(onScan: (ticket: string) => void) {
    const video = ref<HTMLVideoElement | null>(null);
    const active = ref(false);
    const starting = ref(false);
    const message = ref('');
    let stream: MediaStream | null = null;
    let timer: ReturnType<typeof setTimeout> | undefined;
    let generation = 0;

    function stop() {
        generation++;
        clearTimeout(timer);
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        if (video.value) video.value.srcObject = null;
        active.value = false;
        starting.value = false;
    }

    async function start() {
        stop();
        message.value = '';
        const current = generation;
        const Detector = (
            globalThis as typeof globalThis & {
                BarcodeDetector?: QrDetectorConstructor;
            }
        ).BarcodeDetector;
        if (!globalThis.isSecureContext) {
            message.value =
                'Camera scanning needs HTTPS or localhost. Use attendee search below.';
            return;
        }
        if (
            !Detector ||
            typeof navigator === 'undefined' ||
            !navigator.mediaDevices?.getUserMedia
        ) {
            message.value =
                'QR scanning is unavailable in this browser. Use attendee search below.';
            return;
        }
        starting.value = true;
        try {
            const formats = await Detector.getSupportedFormats();
            if (generation !== current) return;
            if (!formats.includes('qr_code')) {
                message.value =
                    'QR scanning is unavailable in this browser. Use attendee search below.';
                return;
            }
            const acquired = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: { ideal: 'environment' } },
            });
            if (generation !== current) {
                acquired.getTracks().forEach((track) => track.stop());
                return;
            }
            stream = acquired;
            const preview = video.value;
            if (!preview) {
                stop();
                return;
            }
            preview.srcObject = acquired;
            await preview.play();
            if (generation !== current) return;
            active.value = true;
            const detector = new Detector({ formats: ['qr_code'] });
            const scan = async () => {
                if (generation !== current) return;
                try {
                    if (preview.readyState >= 2) {
                        const codes = await detector.detect(preview);
                        if (generation !== current) return;
                        const code = codes.find(
                            (code) => code.rawValue.trim() !== '',
                        );
                        if (code) {
                            stop();
                            onScan(code.rawValue);
                            return;
                        }
                    }
                    timer = setTimeout(() => void scan(), 200);
                } catch {
                    if (generation !== current) return;
                    stop();
                    message.value =
                        'The camera could not read a QR ticket. Use attendee search below or try again.';
                }
            };
            void scan();
        } catch {
            if (generation !== current) return;
            stop();
            message.value =
                'Camera access was denied or the camera is unavailable. You can still find and check in attendees by name below.';
        } finally {
            if (generation === current) starting.value = false;
        }
    }

    function onVisibilityChange() {
        if (document.hidden) stop();
    }
    onMounted(() => {
        if (typeof document !== 'undefined')
            document.addEventListener('visibilitychange', onVisibilityChange);
    });
    onBeforeUnmount(() => {
        stop();
        if (typeof document !== 'undefined')
            document.removeEventListener(
                'visibilitychange',
                onVisibilityChange,
            );
    });
    return { video, active, starting, message, start, stop };
}
