import React from 'react';
import { formatChangeDate } from '../../Helpers/format';

type DiffProps = {
    encodedDateTimeData: string;
};

const DateTimePropertyDiff: React.FC<DiffProps> = ({ encodedDateTimeData }) => {
    return <time>{encodedDateTimeData ? formatChangeDate(encodedDateTimeData) : '-'}</time>;
};

export default React.memo(DateTimePropertyDiff);
