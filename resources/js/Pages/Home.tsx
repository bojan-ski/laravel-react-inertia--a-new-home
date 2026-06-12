import { Head } from '@inertiajs/react'

export default function Home({ appName, laravelVersion, phpVersion }) {
    return (
        <>
            <Head title="Home" />
            <div className="min-h-screen flex items-center justify-center bg-gray-50">
                <div className="max-w-lg text-center space-y-4">
                    <h1 className="text-4xl font-bold text-gray-900">
                        Welcome to {appName}
                    </h1>
                    <p className="text-gray-500">
                        Laravel {laravelVersion} · PHP {phpVersion} · React 19 · Inertia 3
                    </p>
                    <p className="text-sm text-gray-400">
                        Built from scratch — no starter kits.
                    </p>
                </div>
            </div>
        </>
    )
}