import '../css/app.css'
import { createInertiaApp } from '@inertiajs/react'

createInertiaApp({
    title: (title) => (title ? `${title} — ${import.meta.env.VITE_APP_NAME}` : import.meta.env.VITE_APP_NAME),
    pages: {
        extension: ['.tsx', '.jsx'],
    },
})