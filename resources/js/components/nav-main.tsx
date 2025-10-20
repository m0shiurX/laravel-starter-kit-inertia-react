import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { NavSection, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';

export function NavMain({ items = [], sections = [] }: { items: NavItem[]; sections?: NavSection[] }) {
    const page = usePage();

    const { state } = useSidebar();
    const [expandedItems, setExpandedItems] = useState<Set<string>>(new Set());
    const [hoveredItem, setHoveredItem] = useState<string | null>(null);
    const [dropdownPosition, setDropdownPosition] = useState<{ top: number; left: number } | null>(null);
    const isCollapsed = state === 'collapsed';
    const toggleExpanded = (itemTitle: string) => {
        if (isCollapsed) return; // Don't expand in collapsed mode

        const newExpanded = new Set<string>();

        // If the clicked item is already expanded, close it (accordion behavior)
        if (!expandedItems.has(itemTitle)) {
            newExpanded.add(itemTitle);
        }
        // If it was expanded, leave the set empty (close it)

        setExpandedItems(newExpanded);
    };

    const isItemActive = (item: NavItem): boolean => {
        if (item.href === page.url) return true;
        if (item.childRoutes) {
            return item.childRoutes.some((child) => child.href === page.url);
        }
        return false;
    };

    const isChildActive = (child: NavItem): boolean => {
        return child.href === page.url;
    };

    const handleMouseEnter = (item: NavItem, event: React.MouseEvent<HTMLDivElement>) => {
        if (isCollapsed && item.childRoutes) {
            const rect = event.currentTarget.getBoundingClientRect();
            setDropdownPosition({
                top: rect.top,
                left: rect.right + 8,
            });
            setHoveredItem(item.title);
        }
    };

    const handleMouseLeave = () => {
        // Use a small delay to allow moving to the dropdown
        setTimeout(() => {
            setHoveredItem(null);
            setDropdownPosition(null);
        }, 100);
    };

    const renderNavItem = (item: NavItem) => (
        <SidebarMenuItem key={item.title} className="relative">
            {item?.childRoutes ? (
                <>
                    {/* Parent Menu Item with Children */}
                    <div className="group relative" onMouseEnter={(e) => handleMouseEnter(item, e)} onMouseLeave={handleMouseLeave}>
                        <button
                            onClick={() => toggleExpanded(item.title)}
                            className={`group/parent relative flex h-8 w-full cursor-pointer items-center gap-3 rounded-md border px-3 text-sm transition-all duration-200 ease-in-out hover:bg-blue-100/50 hover:text-blue-800 ${isCollapsed ? 'justify-center' : ''
                                } ${isItemActive(item)
                                    ? 'border-blue-100 bg-blue-100/50 text-blue-800 shadow-sm dark:bg-zinc-800 dark:border-zinc-600 dark:text-white '
                                    : 'border-transparent text-zinc-800 font-semibold hover:border-white/5 dark:hover:border-zinc-600 dark:hover:bg-zinc-800 dark:text-zinc-400'
                                }`}
                        >
                            <div className="flex justify-center items-center aspect-square">
                                {item.icon && <item.icon className="size-5" />}
                            </div>
                            {!isCollapsed && (
                                <>
                                    <div className="flex-1 text-left truncate">{item.title}</div>
                                    <div className="flex items-center gap-1">
                                        {item.badge && (
                                            <span className="inline-flex justify-center items-center bg-red-500 px-1.5 py-0.5 rounded-full min-w-[16px] h-4 font-bold text-white text-xs leading-none">
                                                {item.badge}
                                            </span>
                                        )}
                                        <ChevronRight
                                            className={`h-4 w-4 transition-transform duration-200 ease-in-out ${expandedItems.has(item.title) ? 'rotate-90' : ''
                                                }`}
                                        />
                                    </div>
                                </>
                            )}
                        </button>

                        {/* Floating dropdown for collapsed mode */}
                        {isCollapsed && hoveredItem === item.title && item.childRoutes && dropdownPosition && (
                            <div
                                className="z-[100] fixed bg-white dark:bg-[#2C2C2C] shadow-xl backdrop-blur-sm border dark:border-white/10 rounded-md w-56"
                                style={{
                                    left: dropdownPosition.left,
                                    top: dropdownPosition.top,
                                    transform: 'translateY(-50%)',
                                }}
                                onMouseEnter={() => setHoveredItem(item.title)}
                                onMouseLeave={handleMouseLeave}
                            >
                                <div className="p-1">
                                    <div className="mb-1 px-3 py-2 dark:border-white/10 border-b font-semibold text-xs dark:text-white">{item.title}</div>
                                    {item.childRoutes.map((child: NavItem, index: number) => (
                                        <Link
                                            key={index}
                                            href={child.href || '#'}
                                            className={`block w-full rounded-md px-3 py-2 text-zinc-300 text-sm transition-all duration-200 ease-in-out  bg-white hover:bg-blue-100 hover:text-blue-800 ${isChildActive(child) ? 'border-blue-100 bg-blue-100/50 text-blue-800 shadow-sm dark:bg-zinc-800 dark:border-zinc-600 dark:text-white ' : 'text-zinc-800 font-semibold dark:hover:border-zinc-600 dark:hover:bg-zinc-800 dark:text-zinc-400'
                                                }`}
                                        >
                                            {child.title}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Submenu with smooth animation - only show when not collapsed */}
                    {!isCollapsed && (
                        <div
                            className={`overflow-hidden transition-all duration-200 ease-in-out ${expandedItems.has(item.title) ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'
                                }`}
                            onClick={(e) => e.stopPropagation()} // Prevent clicks inside submenu from bubbling up
                        >
                            {item.childRoutes && item.childRoutes.length > 0 && (
                                <div className="relative flex flex-col gap-1 mt-1 pb-1">
                                    {item.childRoutes.map((child: NavItem, index: number) => (
                                        <Link
                                            key={index}
                                            href={child.href || '#'}
                                            className={`group relative z-10 flex h-9 w-full cursor-pointer items-center gap-3 rounded-md border px-3 pl-10 text-sm transition-all duration-200 ease-in-out hover:bg-blue-100/80 hover:text-blue-800 ${isChildActive(child)
                                                ? 'border-blue-100 bg-blue-100/50 text-blue-800  dark:bg-zinc-800 dark:border-zinc-600 dark:text-white  shadow-sm'
                                                : 'border-transparent text-zinc-500  hover:border-white/5 dark:hover:border-zinc-600 dark:hover:bg-zinc-800 dark:text-zinc-400'
                                                }`}
                                            onClick={(e) => e.stopPropagation()} // Prevent child clicks from bubbling up
                                            prefetch
                                        >
                                            <div className="flex-1 text-left truncate">{child.title}</div>
                                            {/* Active indicator */}
                                            {isChildActive(child) && (
                                                <div className="top-1/2 left-4.5 absolute bg-white rounded-full w-1 h-1 -translate-y-1/2"></div>
                                            )}
                                        </Link>
                                    ))}
                                    {/* Connecting line for submenu */}
                                    <div className="top-1 left-5 absolute bg-gradient-to-b from-white/10 to-white/5 w-px h-[calc(100%-0.5rem)]"></div>
                                </div>
                            )}
                        </div>
                    )}
                </>
            ) : (
                /* Simple Menu Item without Children */
                <Link
                    href={item.href || '#'}
                    className={`group/parent relative flex h-8 w-full cursor-pointer items-center gap-3 rounded-md border px-3 text-sm transition-all duration-200 ease-in-out hover:bg-blue-100/50 hover:text-blue-800 ${isCollapsed ? 'justify-center' : ''
                        } ${isItemActive(item)
                            ? 'border-blue-100 bg-blue-100/50 text-blue-800 dark:bg-zinc-800 dark:border-zinc-600 dark:text-white  shadow-sm'
                            : 'border-transparent text-zinc-800 font-semibold hover:border-blue-100 dark:hover:border-zinc-600 dark:hover:bg-zinc-800 dark:text-zinc-400'
                        }`}
                >
                    <div className="flex justify-center items-center aspect-square">{item.icon && <item.icon className="size-5" />}</div>
                    {!isCollapsed && (
                        <>
                            <div className="flex-1 text-left truncate">{item.title}</div>
                            <div className="flex items-center gap-1">
                                {item.badge && (
                                    <span className="inline-flex justify-center items-center bg-red-500 px-1.5 py-0.5 rounded-full min-w-[16px] h-4 font-bold text-white text-xs leading-none">
                                        {item.badge}
                                    </span>
                                )}
                                {/* Active indicator */}
                                {isItemActive(item) && <div className="bg-white rounded-full w-1 h-1"></div>}
                            </div>
                        </>
                    )}
                </Link>
            )}
        </SidebarMenuItem>
    );
    return (
        <SidebarGroup className="flex flex-col gap-2 p-2 w-full overflow-y-auto scrollbar-hide">
            {sections.length > 0 ? (
                // Render organized sections
                <div className="space-y-2">
                    {sections.map((section, sectionIndex) => (
                        <div key={section.title || sectionIndex} className="">
                            {section.title && !isCollapsed && (
                                <div className="px-3 py-2 font-semibold text-neutral-500 text-xs uppercase tracking-wider">{section.title}</div>
                            )}
                            <SidebarMenu className="space-y-1">{section.items.map(renderNavItem)}</SidebarMenu>
                            {/* Add separator between sections (except last) - only when not collapsed */}
                            {!isCollapsed && sectionIndex < sections.length - 1 && (
                                <div className="bg-gradient-to-r from-transparent via-white/10 to-transparent my-2 h-px"></div>
                            )}
                        </div>
                    ))}
                </div>
            ) : (
                // Render flat items (fallback)
                <SidebarMenu className="space-y-1">{items.map(renderNavItem)}</SidebarMenu>
            )}
        </SidebarGroup>
    );
}
