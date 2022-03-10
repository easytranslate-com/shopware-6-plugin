import './page/easytranslate-project-list';
import './page/easytranslate-project-detail';

import daDK from './snippet/da_DK.json';
import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

const { Module } = Shopware;

Module.register('easytranslate-project', {
    type: 'plugin',
    name: 'easytranslate-project',
    title: 'easytranslate-project.menu.title',
    description: 'easytranslate-project.menu.descriptionTextModule',

    snippets: {
        "da-DK": daDK,
        "de-DE": deDE,
        "en-GB": enGB,
    },

    routes: {
        index: {
            components: {
                default: 'easytranslate-project-list',
            },
            path: 'index',
            meta: {
                privilege: 'project.viewer',
                appSystem: {
                    view: 'list',
                },
            },
        },

        create: {
            component: 'easytranslate-project-detail',
            path: 'create',
            meta: {
                privilege: 'project.creator',
            },
        },

        detail: {
            component: 'easytranslate-project-detail',
            path: 'detail/:id?',
            meta: {
                privilege: 'project.viewer',
                appSystem: {
                    view: 'detail',
                },
            },
            props: {
                default: (route) => {
                    return {
                        projectId: route.params.id,
                    };
                },
            },
        },
    },

    navigation: [{
        id: 'easytranslate-project',
        label: 'easytranslate-project.menu.title',
        path: 'easytranslate.project.index',
        parent: 'sw-content',
        position: 10,
    }],
});
