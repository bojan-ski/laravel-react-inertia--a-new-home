import { configureEcho } from '@laravel/echo-react'

configureEcho({
    broadcaster: import.meta.env.VITE_BROADCAST_CONNECTION || 'reverb',
})
