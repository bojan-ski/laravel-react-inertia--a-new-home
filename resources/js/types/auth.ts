export interface LoginTranslations {
    title: string
    subtitle: string
    email: string
    password: string
    forgot: string
    submit: string
    no_account: string
    create_one: string
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
}

export interface LoginPageProps {
    translations: LoginTranslations
}

export interface RegisterPageProps {
    translations: RegisterTranslations
}
