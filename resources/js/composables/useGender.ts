import { GenderEnum } from '@/types/shared';

export default function Gender(g: number) {
    return g === GenderEnum.male ? 'Male' : 'Female';
}
