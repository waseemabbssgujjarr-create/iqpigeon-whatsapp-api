const styles = {
    complete: 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30',
    in_progress: 'bg-amber-500/15 text-amber-200 ring-amber-500/30',
    not_started: 'bg-slate-700/80 text-slate-400 ring-slate-600',
};

const labels = {
    complete: 'Complete',
    in_progress: 'In progress',
    not_started: 'Not started',
};

export default function StepStatusBadge({ status }) {
    return (
        <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ${styles[status] ?? styles.not_started}`}>
            {labels[status] ?? status}
        </span>
    );
}
