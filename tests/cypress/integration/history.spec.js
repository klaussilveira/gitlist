describe('History page', () => {
  it('view file history', () => {
    cy.visit('/git-bare-repo/history/a003d30bc7a355f55bf28479e62134186bae1aed/mm/cma.c');

    cy.get('.breadcrumb > .active').should('have.text', '\n            cma.c\n          ');
    cy.get('.card-header').should('have.text', '\n            November 24, 2016\n          ');
  });
});
