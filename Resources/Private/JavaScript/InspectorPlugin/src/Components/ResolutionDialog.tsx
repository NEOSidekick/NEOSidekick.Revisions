import React, { useState } from 'react';
import { Button, Dialog } from '@neos-project/react-ui-components';

import { ResolutionChoice, ResolutionRow, Resolutions } from '../Interfaces/Resolution';

type ResolutionDialogProps = {
    rows: ResolutionRow[];
    errors: string[];
    previousResolutions: Resolutions;
    isLoading: boolean;
    translate: (id: string, fallback?: string, params?: Record<string, unknown> | string[]) => string;
    onApply: (resolutions: Resolutions) => void;
    onCancel: () => void;
};

const getPreviousChoice = (resolutions: Resolutions, identifier: string): ResolutionChoice | undefined =>
    (Object.keys(resolutions[identifier] || {}) as ResolutionChoice[]).find(
        (choice) => resolutions[identifier][choice]
    );

const ResolutionDialog: React.FC<ResolutionDialogProps> = ({
    rows,
    errors,
    previousResolutions,
    isLoading,
    translate,
    onApply,
    onCancel,
}) => {
    const [choices, setChoices] = useState<Record<string, ResolutionChoice>>(() =>
        rows.reduce((initialChoices, row) => {
            const choice = row.resolution || getPreviousChoice(previousResolutions, row.identifier);
            return choice && row.choices.includes(choice)
                ? { ...initialChoices, [row.identifier]: choice }
                : initialChoices;
        }, {})
    );
    const isComplete = rows.length > 0 && rows.every((row) => choices[row.identifier]);

    const apply = () =>
        onApply(
            rows.reduce(
                (resolutions, row) => ({ ...resolutions, [row.identifier]: { [choices[row.identifier]]: true } }),
                {}
            )
        );

    const describeProblem = (row: ResolutionRow, problem: ResolutionRow['problems'][number]): string => {
        switch (problem.id) {
            case 'nodeTypeMissing':
                return translate('resolution.problem.nodeTypeMissing', 'The node type "{nodeType}" no longer exists.', {
                    nodeType: row.nodeType.name,
                });
            case 'nodeTypeNotAllowed':
                return translate(
                    'resolution.problem.nodeTypeNotAllowed',
                    'The node type "{nodeType}" is no longer allowed here.',
                    { nodeType: row.nodeType.label }
                );
            case 'parentMissing':
                return translate(
                    'resolution.problem.parentMissing',
                    'The place of this content in the revision no longer exists.'
                );
            case 'movedAway':
                return row.document
                    ? translate('resolution.problem.movedAway', 'Moved to the page "{document}" since the revision.', {
                          document: row.document.label,
                      })
                    : translate(
                          'resolution.problem.movedAway.unknown',
                          'Moved to an unknown place since the revision.'
                      );
            case 'movedHere':
                return translate('resolution.problem.movedHere', 'Moved to this page since the revision.');
            default:
                return problem.message;
        }
    };

    const labelChoice = (row: ResolutionRow, choice: ResolutionChoice): string => {
        if (choice === '__moveBack') {
            return translate('resolution.choice.moveBack', 'Move back');
        }
        if (choice === '__remove') {
            return translate('resolution.choice.remove', 'Remove');
        }
        if (row.problems.some((problem) => problem.id === 'movedAway')) {
            return translate('resolution.choice.leaveThere', 'Leave there');
        }
        if (row.problems.some((problem) => problem.id === 'movedHere')) {
            return translate('resolution.choice.keep', 'Keep');
        }
        return translate('resolution.choice.skip', 'Skip');
    };

    return (
        <Dialog
            isOpen
            title={translate('resolution.title', 'Decide how to apply the revision')}
            onRequestClose={onCancel}
            type="warn"
            style="wide"
            actions={[
                <Button key="cancel" style="lighter" hoverStyle="brand" onClick={onCancel}>
                    {translate('resolution.cancel', 'Cancel')}
                </Button>,
                // No choice in the dialog fixes an error, so applying again would only repeat it
                ...(errors.length > 0
                    ? []
                    : [
                          <Button
                              key="apply"
                              style="success"
                              hoverStyle="success"
                              disabled={!isComplete || isLoading}
                              onClick={apply}
                          >
                              {translate('resolution.apply', 'Apply revision')}
                          </Button>,
                      ]),
            ]}
        >
            <div style={{ padding: '16px' }}>
                {errors.length > 0 && (
                    <div style={{ color: 'red', marginBottom: '1rem', whiteSpace: 'pre-line' }} role="alert">
                        {[translate('resolution.errors', 'The revision cannot be applied like this:'), ...errors].join(
                            '\n'
                        )}
                    </div>
                )}
                <p style={{ marginBottom: '1rem' }}>
                    {translate(
                        'resolution.description',
                        'Some content changed since the revision in a way that needs a decision.'
                    )}
                </p>
                {rows.map((row) => (
                    <div key={row.identifier} style={{ padding: '0.75rem 0', borderTop: '1px solid #3f3f3f' }}>
                        <div>
                            <strong>{row.label}</strong>{' '}
                            <span style={{ opacity: 0.7 }}>
                                {[
                                    row.nodeType.label,
                                    ...row.dimensions.map((dimensions) =>
                                        Object.values(dimensions)
                                            .map((values) => values[0])
                                            .join('/')
                                    ),
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </span>
                        </div>
                        {row.problems.map((problem) => (
                            <div key={problem.id}>{describeProblem(row, problem)}</div>
                        ))}
                        <div style={{ display: 'flex', gap: '0.5rem', marginTop: '0.5rem' }}>
                            {row.choices.map((choice) => (
                                <Button
                                    key={choice}
                                    size="small"
                                    style={choices[row.identifier] === choice ? 'brand' : 'lighter'}
                                    hoverStyle="brand"
                                    onClick={() => setChoices({ ...choices, [row.identifier]: choice })}
                                >
                                    {labelChoice(row, choice)}
                                </Button>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </Dialog>
    );
};

export default ResolutionDialog;
