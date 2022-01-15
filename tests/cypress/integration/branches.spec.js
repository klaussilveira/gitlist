describe('Repository branches page', () => {
  it('successfully loads', () => {
    cy.visit('/git-bare-repo/branches');

    cy.get('.card-header').should('have.text', '\n        Remote branches\n      ');
    cy.get(':nth-child(2) > h5 > a').should('have.text', '\n                  master\n                ');
  });
});
