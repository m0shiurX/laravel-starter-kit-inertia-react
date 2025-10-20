import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type Business } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';

interface Props {
    business: Business;
}

export default function Edit({ business }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: dashboard().url,
        },
        {
            title: business.name,
            href: `/businesses/${business.id}/edit`,
        },
        {
            title: 'Settings',
            href: `/businesses/${business.id}/edit`,
        },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        name: business.name,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/businesses/${business.id}`);
    };

    const handleDelete = () => {
        if (
            confirm(
                `Are you sure you want to delete ${business.name}? This action cannot be undone.`,
            )
        ) {
            router.delete(`/businesses/${business.id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${business.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="mx-auto w-full max-w-2xl space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Business Settings</CardTitle>
                            <CardDescription>
                                Update your business information
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Business Name</Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        className={
                                            errors.name ? 'border-red-500' : ''
                                        }
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-red-600">
                                            {errors.name}
                                        </p>
                                    )}
                                </div>

                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        {processing
                                            ? 'Saving...'
                                            : 'Save Changes'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="border-red-200 dark:border-red-900">
                        <CardHeader>
                            <CardTitle className="text-red-600 dark:text-red-400">
                                Danger Zone
                            </CardTitle>
                            <CardDescription>
                                Permanently delete this business and all
                                associated data
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button
                                variant="destructive"
                                onClick={handleDelete}
                            >
                                Delete Business
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
