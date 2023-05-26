import React from 'react';

type DiffProps = {
    diff: TextDiff;
    original?: string;
    changed?: string;
};

const TextPropertyDiff: React.FC<DiffProps> = ({ diff, original, changed }) => {
    return Array.isArray(diff) ? (
        <>
            {diff.map((blocks) =>
                blocks.map((block, index) => (
                    <tr key={index}>
                        <td
                            dangerouslySetInnerHTML={{
                                __html: block.base.lines.join(),
                            }}
                            style={{ color: '#ff460d', textAlign: 'left' }}
                        />
                        <td
                            dangerouslySetInnerHTML={{
                                __html: block.changed.lines.join(),
                            }}
                            style={{ color: '#00a338', textAlign: 'left' }}
                        />
                    </tr>
                ))
            )}
        </>
    ) : (
        <tr>
            <td style={{ color: '#ff460d', textAlign: 'left' }}>
                {original ? original : '-'}
            </td>
            <td style={{ color: '#00a338', textAlign: 'left' }}>
                {changed ? changed : '-'}
            </td>
        </tr>
    );
};

export default React.memo(TextPropertyDiff);
