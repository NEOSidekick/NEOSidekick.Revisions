import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { $transform, $get } from 'plow-js';

// @ts-ignore
import { selectors, actions } from '@neos-project/neos-ui-redux-store';
// @ts-ignore
import { neos } from '@neos-project/neos-ui-decorators';

import RevisionList from './Components/RevisionList';
import Node from './Interfaces/Node';
import I18nRegistry from './Interfaces/I18nRegistry';

// @ts-ignore
@connect((state) => ({
    getNodeByContextPath: selectors.CR.Nodes.nodeByContextPath(state),
}))
// @ts-ignore
@connect(
    $transform({
        documentNodePath: $get('cr.nodes.documentNode'),
        contentDimensions: selectors.CR.ContentDimensions.byName,
        allowedPresets: selectors.CR.ContentDimensions.allowedPresets,
        activePresets: selectors.CR.ContentDimensions.activePresets,
    }),
    {
        addFlashMessage: actions.UI.FlashMessages.add,
        reloadDocument: actions.UI.ContentCanvas.reload,
    }
)
@neos((globalRegistry) => ({
    i18nRegistry: globalRegistry.get('i18n'),
    frontendConfiguration: globalRegistry.get('frontendConfiguration').get('CodeQ.Revisions'),
}))
export default class RevisionsView extends PureComponent<{
    documentNodePath: string;
    getNodeByContextPath: (contextPath: string) => Node;
    addFlashMessage: (title: string, message: string, severity?: string, timeout?: number) => void;
    reloadDocument: () => void;
    i18nRegistry: I18nRegistry;
    renderSecondaryInspector: (identifier: string, component: () => React.ReactNode) => void;
    frontendConfiguration: {
        [key: string]: any;
        showDeleteButton: boolean;
    };
    contentDimensions: ContentDimensions;
}> {
    static propTypes = {
        documentNodePath: PropTypes.string.isRequired,
        i18nRegistry: PropTypes.object.isRequired,
        getNodeByContextPath: PropTypes.func.isRequired,
        addFlashMessage: PropTypes.func.isRequired,
        reloadDocument: PropTypes.func.isRequired,
        frontendConfiguration: PropTypes.object.isRequired,
        renderSecondaryInspector: PropTypes.func.isRequired,
    };

    constructor(props) {
        super(props);
    }

    render() {
        const documentNode = this.props.getNodeByContextPath(this.props.documentNodePath);
        const {
            addFlashMessage,
            reloadDocument,
            i18nRegistry,
            frontendConfiguration,
            renderSecondaryInspector,
            contentDimensions,
        } = this.props;

        return (
            <RevisionList
                documentNode={documentNode}
                addFlashMessage={addFlashMessage}
                reloadDocument={reloadDocument}
                i18nRegistry={i18nRegistry}
                frontendConfiguration={frontendConfiguration}
                renderSecondaryInspector={renderSecondaryInspector}
                contentDimensions={contentDimensions}
            />
        );
    }
}
