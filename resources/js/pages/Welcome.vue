<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { dashboard, home, login, register } from '@/routes';

defineProps<{ canRegister: boolean }>();

const page = usePage();

const essentials = [
    {
        number: '01',
        title: 'Stay informed',
        description: 'Read Announcements from the association.',
    },
    {
        number: '02',
        title: 'Keep track of dues',
        description: 'View your Property’s Statement of Account.',
    },
    {
        number: '03',
        title: 'Keep details current',
        description: 'Maintain your shared Property Profile.',
    },
];
</script>

<template>
    <div class="landing min-h-screen">
        <Head title="Blessed Sacrament Residences Homeowners Association">
            <meta
                name="description"
                content="Stay connected with Blessed Sacrament Residences Homeowners Association. Read Announcements, view your Statement of Account, and maintain your Property Profile."
            />
            <link rel="preconnect" href="https://rsms.me/" />
            <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        </Head>

        <a
            href="#community"
            class="landing-focus sr-only rounded-lg bg-[var(--landing-button)] px-6 py-4 text-[var(--landing-button-text)] focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-10"
        >
            Skip to content
        </a>

        <header class="border-b border-[var(--landing-border)]">
            <div
                class="mx-auto flex max-w-[1440px] flex-wrap items-center justify-between gap-6 px-6 py-6 sm:px-10 lg:px-20 lg:py-[30px]"
            >
                <Link
                    :href="home()"
                    class="landing-focus flex items-center gap-3.5 rounded-sm"
                    aria-label="Blessed Sacrament Residences Homeowners Association home"
                >
                    <span
                        aria-hidden="true"
                        class="landing-heading flex size-[42px] shrink-0 items-center justify-center rounded-full border border-[var(--landing-green)] text-lg text-[var(--landing-green)]"
                    >
                        BSR
                    </span>
                    <span class="flex flex-col gap-1">
                        <span class="text-sm font-semibold sm:text-base">
                            Blessed Sacrament Residences
                        </span>
                        <span class="text-[13px] text-[var(--landing-muted)]">
                            Homeowners Association
                        </span>
                    </span>
                </Link>

                <nav
                    aria-label="Main navigation"
                    class="flex w-full flex-wrap items-center gap-x-6 gap-y-4 text-sm sm:w-auto lg:gap-x-8"
                >
                    <a href="#essentials" class="landing-text-link">
                        Our community
                    </a>
                    <a href="#joining" class="landing-text-link">
                        How it works
                    </a>
                    <a href="#contacts" class="landing-text-link"> Contacts </a>
                    <Link
                        v-if="page.props.auth.user"
                        :href="dashboard()"
                        class="landing-primary-link"
                    >
                        Go to your dashboard
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="landing-text-link">
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="landing-primary-link"
                        >
                            Create an account
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main id="community" tabindex="-1" class="focus:outline-none">
            <section
                aria-labelledby="welcome-heading"
                class="mx-auto grid max-w-[1440px] items-center gap-12 px-6 pt-12 pb-12 sm:px-10 sm:pt-16 lg:grid-cols-[minmax(0,680px)_minmax(0,480px)] lg:gap-20 lg:px-20 lg:pt-[74px] lg:pb-[60px]"
            >
                <div class="flex flex-col gap-6">
                    <p
                        class="text-[13px] tracking-[0.12em] text-[var(--landing-green)] uppercase"
                    >
                        Our neighborhood. Our community.
                    </p>
                    <h1
                        id="welcome-heading"
                        class="landing-heading text-[42px] leading-[1.1] tracking-[-0.035em] sm:text-[56px] lg:text-[64px] lg:leading-[70px]"
                    >
                        A place to belong.<br />
                        A community to<br class="hidden lg:block" />
                        care for.
                    </h1>
                    <p
                        class="max-w-[510px] text-base leading-[29px] text-[var(--landing-muted)] sm:text-lg"
                    >
                        Welcome to the Blessed Sacrament Residences Homeowners
                        Association. Stay connected with the community you call
                        home.
                    </p>
                    <div
                        class="flex flex-wrap items-center gap-6 pt-2 text-[15px]"
                    >
                        <Link
                            v-if="page.props.auth.user"
                            :href="dashboard()"
                            class="landing-primary-link px-6 py-4"
                        >
                            Go to your dashboard
                            <ArrowRight aria-hidden="true" class="size-4" />
                        </Link>
                        <template v-else>
                            <Link
                                :href="login()"
                                class="landing-primary-link px-6 py-4"
                            >
                                Log in to your account
                                <ArrowRight aria-hidden="true" class="size-4" />
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="register()"
                                class="landing-text-link"
                            >
                                New here? Create an account
                            </Link>
                        </template>
                    </div>
                </div>

                <div
                    class="mx-auto flex w-full max-w-[480px] flex-col items-center gap-6 rounded-t-[160px] rounded-b-2xl bg-[var(--landing-surface)] px-6 pt-12 pb-8 sm:px-[38px] sm:pt-[65px] lg:mx-0 lg:min-h-[410px]"
                >
                    <svg
                        aria-hidden="true"
                        focusable="false"
                        width="300"
                        height="190"
                        viewBox="0 0 300 190"
                        fill="none"
                        class="h-auto w-full max-w-[300px] text-[var(--landing-green)]"
                    >
                        <path
                            d="M10 170H290M42 170V90L93 48L144 90V170M58 93H127M75 170V125H107V170M170 170V102L214 65L258 102V170M187 105H242M201 170V134H227V170"
                            stroke="currentColor"
                            stroke-width="2"
                        />
                        <path
                            d="M20 165V100M15 115C-7 81 12 48 25 50C54 70 49 98 20 113M273 169V87M269 105C251 77 258 45 275 46C300 64 303 94 273 105"
                            stroke="currentColor"
                            stroke-width="2"
                        />
                        <circle
                            cx="152"
                            cy="30"
                            r="15"
                            stroke="currentColor"
                            stroke-opacity="0.6"
                            stroke-width="2"
                        />
                    </svg>
                    <p
                        class="landing-heading text-center text-[27px] leading-[34px]"
                    >
                        Good neighbors.<br />
                        Shared responsibilities.
                    </p>
                    <p
                        class="text-center text-sm leading-[22px] text-[var(--landing-muted)]"
                    >
                        A simpler way to stay informed<br />
                        and look after our neighborhood.
                    </p>
                </div>
            </section>

            <section
                id="essentials"
                aria-labelledby="community-heading"
                class="border-t border-[var(--landing-border)]"
            >
                <div
                    class="mx-auto flex max-w-[1440px] flex-col gap-6 px-6 pt-10 sm:px-10 lg:flex-row lg:items-start lg:justify-between lg:gap-20 lg:px-20 lg:pt-12"
                >
                    <div class="flex flex-col gap-3">
                        <p
                            class="text-[13px] tracking-[0.12em] text-[var(--landing-green)] uppercase"
                        >
                            A shared place for our neighborhood
                        </p>
                        <h2
                            id="community-heading"
                            class="landing-heading text-4xl leading-tight"
                        >
                            Our Community
                        </h2>
                    </div>
                    <p
                        class="max-w-[670px] text-base leading-7 text-[var(--landing-muted)]"
                    >
                        The Blessed Sacrament Residences Homeowners Association
                        portal brings community Announcements, dues, and Payment
                        records into one place. Owners and residents can stay
                        informed, keep track of their Property’s records, and
                        maintain shared household details.
                    </p>
                </div>
                <div
                    class="mx-auto grid max-w-[1440px] gap-8 px-6 pt-10 pb-12 sm:px-10 md:grid-cols-3 md:gap-12 lg:px-20"
                >
                    <div
                        v-for="essential in essentials"
                        :key="essential.number"
                        class="flex flex-col gap-3"
                    >
                        <span
                            aria-hidden="true"
                            class="text-[13px] text-[var(--landing-muted)]"
                        >
                            {{ essential.number }}
                        </span>
                        <h3 class="landing-heading text-2xl leading-[30px]">
                            {{ essential.title }}
                        </h3>
                        <p
                            class="text-[15px] leading-6 text-[var(--landing-muted)]"
                        >
                            {{ essential.description }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                id="joining"
                aria-labelledby="joining-heading"
                class="bg-[var(--landing-surface)]"
            >
                <div
                    class="mx-auto flex max-w-[1440px] flex-col gap-10 px-6 py-12 sm:px-10 lg:px-20"
                >
                    <div class="flex flex-col gap-3">
                        <p
                            class="text-[13px] tracking-[0.12em] text-[var(--landing-green)] uppercase"
                        >
                            New to the neighborhood?
                        </p>
                        <h2
                            id="joining-heading"
                            class="landing-heading text-4xl leading-tight"
                        >
                            How it works
                        </h2>
                        <p
                            class="max-w-[670px] text-base leading-7 text-[var(--landing-muted)]"
                        >
                            A User Account is your login. A Membership connects
                            that account to a Property as an owner or resident.
                        </p>
                    </div>
                    <ol class="grid gap-8 md:grid-cols-3 md:gap-12">
                        <li class="flex flex-col gap-3">
                            <span
                                aria-hidden="true"
                                class="text-[13px] text-[var(--landing-muted)]"
                            >
                                01
                            </span>
                            <h3 class="landing-heading text-2xl leading-[30px]">
                                Start with your account
                            </h3>
                            <p
                                class="text-[15px] leading-6 text-[var(--landing-muted)]"
                            >
                                <template v-if="canRegister">
                                    Create your User Account, or log in if you
                                    already have one.
                                </template>
                                <template v-else>
                                    Log in to your User Account. If you need an
                                    account, ask an Officer how to get access.
                                </template>
                                An account alone does not create a Membership.
                            </p>
                        </li>
                        <li class="flex flex-col gap-3">
                            <span
                                aria-hidden="true"
                                class="text-[13px] text-[var(--landing-muted)]"
                            >
                                02
                            </span>
                            <h3 class="landing-heading text-2xl leading-[30px]">
                                Ask for an invitation
                            </h3>
                            <p
                                class="text-[15px] leading-6 text-[var(--landing-muted)]"
                            >
                                Ask an Officer for a Property Invitation. It
                                connects you to the right Property as an owner
                                or resident.
                            </p>
                        </li>
                        <li class="flex flex-col gap-3">
                            <span
                                aria-hidden="true"
                                class="text-[13px] text-[var(--landing-muted)]"
                            >
                                03
                            </span>
                            <h3 class="landing-heading text-2xl leading-[30px]">
                                Join your Property
                            </h3>
                            <p
                                class="text-[15px] leading-6 text-[var(--landing-muted)]"
                            >
                                Open your invitation link while signed in and
                                accept the Terms of Service and Privacy Policy.
                                You can then view your Statement of Account and
                                update your shared Property Profile.
                            </p>
                        </li>
                    </ol>
                </div>
            </section>
            <section
                id="contacts"
                aria-labelledby="contacts-heading"
                class="border-t border-[var(--landing-border)]"
            >
                <div
                    class="mx-auto flex max-w-[1440px] flex-col gap-10 px-6 py-12 sm:px-10 lg:px-20"
                >
                    <div class="flex flex-col gap-3">
                        <p
                            class="text-[13px] tracking-[0.12em] text-[var(--landing-green)] uppercase"
                        >
                            Find our community
                        </p>
                        <h2
                            id="contacts-heading"
                            class="landing-heading text-4xl leading-tight"
                        >
                            Contacts
                        </h2>
                    </div>
                    <div class="grid gap-10 md:grid-cols-2 md:gap-20">
                        <div class="flex flex-col gap-4">
                            <h3 class="landing-heading text-2xl leading-[30px]">
                                Address
                            </h3>
                            <address
                                class="text-base leading-7 text-[var(--landing-muted)] not-italic"
                            >
                                Blessed Sacrament Residences<br />
                                Cagudoy, Basak<br />
                                Lapu-Lapu City, Cebu, Philippines
                            </address>
                        </div>
                        <dl class="flex flex-col gap-5">
                            <div class="flex flex-col gap-1">
                                <dt class="font-medium">Phone</dt>
                                <dd class="text-[var(--landing-muted)]">
                                    To be announced
                                </dd>
                            </div>
                            <div class="flex flex-col gap-1">
                                <dt class="font-medium">Email</dt>
                                <dd class="text-[var(--landing-muted)]">
                                    To be announced
                                </dd>
                            </div>
                            <div class="flex flex-col gap-1">
                                <dt class="font-medium">Office hours</dt>
                                <dd class="text-[var(--landing-muted)]">
                                    To be announced
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
@reference '../../css/app.css';

.landing {
    --landing-background: var(--background);
    --landing-ink: var(--foreground);
    --landing-muted: var(--muted-foreground);
    --landing-green: var(--primary);
    --landing-surface: var(--accent);
    --landing-border: var(--border);
    --landing-button: var(--primary);
    --landing-button-hover: color-mix(
        in srgb,
        var(--primary),
        var(--foreground) 15%
    );
    --landing-button-text: var(--primary-foreground);

    background: var(--landing-background);
    color: var(--landing-ink);
}

.landing-heading {
    font-family: var(--font-serif);
    font-weight: 400;
}

@layer components {
    .landing-focus,
    .landing-text-link,
    .landing-primary-link {
        @apply focus-visible:outline-2 focus-visible:outline-offset-4;
        outline-color: var(--landing-green);
    }

    .landing-text-link {
        @apply rounded-sm hover:underline hover:underline-offset-4;
    }

    .landing-primary-link {
        @apply inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-[22px] py-[13px];
        background: var(--landing-button);
        color: var(--landing-button-text);
    }

    .landing-primary-link:hover {
        background: var(--landing-button-hover);
    }
}
</style>
