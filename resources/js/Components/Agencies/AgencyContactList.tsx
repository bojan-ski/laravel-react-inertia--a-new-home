import { Globe, Mail, Phone } from 'lucide-react'
import type { AgencyContactListProps } from '@/types/agency'

export default function AgencyContactList({ agency }: AgencyContactListProps) {
    return (
        <div className="flex flex-col gap-1.5 text-sm text-text-muted">
            <a href={`mailto:${agency.email}`} className="flex items-center gap-2 transition-colors hover:text-text">
                <Mail className="size-4 shrink-0" />
                <span className="truncate">{agency.email}</span>
            </a>

            {agency.phone && (
                <a href={`tel:${agency.phone}`} className="flex items-center gap-2 transition-colors hover:text-text">
                    <Phone className="size-4 shrink-0" />
                    <span className="truncate">{agency.phone}</span>
                </a>
            )}

            {agency.website && (
                <a
                    href={agency.website}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-2 text-brand transition-colors hover:text-brand-dark"
                >
                    <Globe className="size-4 shrink-0" />
                    <span className="truncate">{agency.website}</span>
                </a>
            )}
        </div>
    )
}
