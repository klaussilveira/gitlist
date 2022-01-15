describe('Blame page', () => {
  it('view file blame', () => {
    cy.visit('/git-bare-repo/blame/a003d30bc7a355f55bf28479e62134186bae1aed/mm/cma.c');

    cy.get('.breadcrumb > .active').should('have.text', '\n            cma.c\n          ');
    cy.get(':nth-child(1) > .col-3 > a').should('have.text', '\n                      Added mm.\n                    ');
    cy.get(':nth-child(2) > .col-3 > a').should('have.text', '\n                      Fixed mm.\n                    ');
  });
});
