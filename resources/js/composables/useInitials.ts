export function getInitials(fullName?: string): string {
    if (!fullName) return '';

    const names = fullName.trim().split(' ').filter(name => name.length > 0);

    if (names.length === 0) return '';
    if (names.length === 1) return names[0].charAt(0).toUpperCase();

    return `${names[0].charAt(0)}${names[names.length - 1].charAt(0)}`.toUpperCase();
}

interface UseInitialsReturn {
    getInitials: (fullName?: string) => string;
}

export function useInitials(): UseInitialsReturn {
    return { getInitials };
}
