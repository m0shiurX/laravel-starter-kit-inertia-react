import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { ThemeToggler } from '@/components/ui/theme-toggler';
import { NavUser } from '@/components/nav-user';
import Divider from '@/components/ui/divide';
import { Link } from '@inertiajs/react';
import { Activity, Info, Plus, Search, Settings } from 'lucide-react';
import { Input } from '@headlessui/react';
import { Button } from '@/components/ui/button';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex items-center gap-2 px-6 md:px-4 border-sidebar-border/50 border-b h-16 group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 transition-[width,height] ease-linear shrink-0">
            <div className="flex items-center gap-2 w-full">
                <div className='flex flex-1 items-center'>
                    <SidebarTrigger className="-ml-1" />
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
                <div className='flex items-center'>
                    <div className='lg:flex items-center gap-4 hidden mr-4'>
                        <div className='flex items-center gap-3 mr-4'>
                            <div className='flex items-center gap-2 bg-gray-50 dark:bg-zinc-800 px-5 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded-3xl'>
                                <Search className='text-zinc-400 size-5'></Search>
                                <Input type='text' className={'placeholder:text-sm dark:placeholder:text-zinc-400 focus:outline-0 text-zinc-500'} placeholder='search for anything here...'></Input>
                            </div>
                            <Button className='bg-blue-600 rounded-full w-8 h-8'><Plus className='size-6'></Plus></Button>
                        </div>
                        <Link href={''}>
                            <Info className='text-zinc-400'></Info>
                        </Link>
                        <Link href={''}>
                            <Activity className='text-zinc-400'></Activity>
                        </Link>
                        <Link href={'/settings'}>
                            <Settings className='text-zinc-400'></Settings>
                        </Link>
                    </div>
                    <Divider className='mx-4'></Divider>
                    <ThemeToggler />
                    <Divider className='mx-4'></Divider>
                    <NavUser />
                </div>
            </div>
        </header>
    );
}
