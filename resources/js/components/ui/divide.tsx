import React from 'react';

const Divider = ({ className }: { className?: string }) => {
    return (
        <div className={`${className} w-[1px] mx-2 h-8 bg-zinc-300`}></div>
    );
};

export default Divider;