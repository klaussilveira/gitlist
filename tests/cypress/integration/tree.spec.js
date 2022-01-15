describe('Tree navigation', () => {
  it('showing sub-tree and file', () => {
    cy.visit('/git-bare-repo');

    cy.get(':nth-child(1) > .tree-filename > .tree-truncate > a').click();
    cy.get(':nth-child(2) > .tree-filename > .tree-truncate > a').click();
    cy.get('h5').should('have.text', 'Fixed mm.');
    cy.get('.breadcrumb > .active').should('have.text', '\n            cma.c\n          ');
  });
});
