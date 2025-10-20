import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { type Business } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Building2, ChevronsUpDown, Plus } from 'lucide-react';

export function BusinessSwitcher() {
    const page = usePage();
    const currentBusiness = page.props.currentBusiness as Business | null;
    const businesses = page.props.businesses as Business[] | undefined;

    const handleBusinessChange = (businessId: string) => {
        if (businessId === 'create') {
            router.visit('/businesses/create');
        } else {
            // Don't preserve scroll - we want a full reload to update context
            router.post(`/business/switch/${businessId}`);
        }
    };

    if (!businesses || businesses.length === 0) {
        return (
            <button
                onClick={() => router.visit('/businesses/create')}
                className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
            >
                <Plus className="h-4 w-4 text-primary" />
                <span className="flex-1 truncate text-left">
                    Create Business
                </span>
            </button>
        );
    }

    return (
        <Select
            value={currentBusiness?.id.toString() || ''}
            onValueChange={handleBusinessChange}
        >
            <SelectTrigger className="h-8 w-full group-data-[collapsible=icon]:!size-8 group-data-[collapsible=icon]:!p-0">
                <div className="flex items-center gap-2 group-data-[collapsible=icon]:justify-center">
                    <Building2 className="h-4 w-4 shrink-0" />
                    <SelectValue>
                        <span className="truncate group-data-[collapsible=icon]:hidden">
                            {currentBusiness?.name || 'Select business'}
                        </span>
                    </SelectValue>
                    <ChevronsUpDown className="ml-auto h-4 w-4 shrink-0 opacity-50 group-data-[collapsible=icon]:hidden" />
                </div>
            </SelectTrigger>
            <SelectContent>
                {businesses.map((business) => (
                    <SelectItem
                        key={business.id}
                        value={business.id.toString()}
                    >
                        <div className="flex items-center gap-2">
                            <Building2 className="h-4 w-4" />
                            {business.name}
                        </div>
                    </SelectItem>
                ))}
                <SelectItem value="create">
                    <div className="flex items-center gap-2 text-primary">
                        <Plus className="h-4 w-4" />
                        Create Business
                    </div>
                </SelectItem>
            </SelectContent>
        </Select>
    );
}
