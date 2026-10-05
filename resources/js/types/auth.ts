export interface LoginTranslations {
    title: string
    subtitle: string
    email: string
    password: string
    forgot: string
    submit: string
    no_account: string
    create_one: string
    email_placeholder: string
    password_placeholder: string
}

export interface RegisterTranslations {
    title: string
    subtitle: string
    account_type: string
    regular_label: string
    regular_description: string
    agency_label: string
    agency_description: string
    name: string
    email: string
    password: string
    confirm_password: string
    terms_prefix: string
    terms_link: string
    privacy_prefix: string
    privacy_link: string
    submit: string
    have_account: string
    sign_in: string
    name_placeholder: string
    email_placeholder: string
    password_placeholder: string
}

export interface ForgotPasswordTranslations {
    title: string
    subtitle: string
    email: string
    submit: string
    back_to_login: string
    email_placeholder: string
}

export interface ResetPasswordTranslations {
    title: string
    subtitle: string
    email: string
    password: string
    confirm_password: string
    submit: string
    password_placeholder: string
}

export interface AuthLayoutTranslations {
    back_home: string
    copyright: string
}

export type AuthImage = 'sign-in' | 'register' | 'password'

export interface AuthHeaderProps {
    title: string
    subtitle: string
}

export interface AuthSwitchPromptProps {
    text: string
    linkLabel: string
    href: string
}

export interface LoginPageProps {
    translations: LoginTranslations
    layout: AuthLayoutTranslations
    email: string
}

export interface RegisterPageProps {
    translations: RegisterTranslations
    layout: AuthLayoutTranslations
}

export interface ForgotPasswordPageProps {
    translations: ForgotPasswordTranslations
    layout: AuthLayoutTranslations
}

export interface ResetPasswordPageProps {
    translations: ResetPasswordTranslations
    layout: AuthLayoutTranslations
    token: string
    email: string
}
