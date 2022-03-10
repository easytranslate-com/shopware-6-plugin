const { Component } = Shopware;

Component.override('sw-many-to-many-assignment-card', {
    methods: {
        onItemSelect(item) {
            if (this.isSelected(item)) {
                this.removeItem(item);
                return;
            }

            if (this.localMode) {
                if (!this.gridData) {
                    this.gridData = EntityCollection.fromCollection(this.entityCollection);
                }
                this.gridData.push(item);
                this.selectedIds = this.gridData.getIds();

                this.$emit('change', this.gridData);
                return;
            }

            this.assignmentRepository.assign(item.id, this.context).then(() => {
                this.selectedIds.push(item.id);
            });
        },

        removeItem(item) {
            if (this.localMode) {
                const newCollection = this.gridData.filter((selected) => {
                    return selected.id !== item.id;
                });

                this.selectedIds = newCollection.getIds();
                this.gridData = newCollection;

                this.$emit('change', newCollection);
                return Promise.resolve();
            }

            return this.assignmentRepository.delete(item.id, this.context).then(() => {
                this.selectedIds = this.selectedIds.filter((selectedId) => {
                    return selectedId !== item.id;
                });
            });
        },
    },
})
