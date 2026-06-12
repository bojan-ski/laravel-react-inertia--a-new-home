export interface SharedProps {
    name: string
    auth: {
        user: AuthUser | null
    }
    flash: {
        success: string | null
        error: string | null
    }
    [key: string]: unknown
}

export interface AuthUser {
    id: number
    name: string
    email: string
}
