import '../css/app.css'
import './echo'
import { createInertiaApp } from '@inertiajs/react'
import AppShell from './Layouts/AppShell'

createInertiaApp({
    title: (title) => (title ? `${title} — ${import.meta.env.VITE_APP_NAME}` : import.meta.env.VITE_APP_NAME),
    layout: () => AppShell,
    pages: {
        extension: ['.tsx', '.jsx'],
    },
})