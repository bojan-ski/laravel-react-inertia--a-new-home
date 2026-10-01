import '../css/app.css'
import './echo'
import { createInertiaApp } from '@inertiajs/react'
import AppShell from './Layouts/AppShell'

const appName = import.meta.env.VITE_APP_NAME
const titleSuffix = ` — ${appName}`

createInertiaApp({
    serverHead: true,
    title: (title) => {
        if (!title || title === appName) {
            return appName
        }

        return title.endsWith(titleSuffix) ? title : `${title}${titleSuffix}`
    },
    layout: () => AppShell,
    pages: {
        extension: ['.tsx', '.jsx'],
    },
})
