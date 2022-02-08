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
    }),
    {
        addFlashMessage: actions.UI.FlashMessages.add,
        reloadDocument: actions.UI.ContentCanvas.reload,
    }
)
@neos((globalRegistry) => ({
    i18nRegistry: globalRegistry.get('i18n'),
}))
export default class RevisionsView extends PureComponent<{
    documentNodePath: string;
    getNodeByContextPath: (contextPath: string) => Node;
    addFlashMessage: (title: string, message: string, severity?: string, timeout?: number) => void;
    reloadDocument: () => void;
    i18nRegistry: I18nRegistry;
}> {
    static propTypes = {
        documentNodePath: PropTypes.string.isRequired,
        i18nRegistry: PropTypes.object.isRequired,
        getNodeByContextPath: PropTypes.func.isRequired,
        addFlashMessage: PropTypes.func.isRequired,
        reloadDocument: PropTypes.func.isRequired,
    };

    constructor(props) {
        super(props);
    }

    render() {
        const documentNode = this.props.getNodeByContextPath(this.props.documentNodePath);

        return (
            <RevisionList
                documentNode={documentNode}
                addFlashMessage={this.props.addFlashMessage}
                reloadDocument={this.props.reloadDocument}
                i18nRegistry={this.props.i18nRegistry}
            />
        );
    }
}
