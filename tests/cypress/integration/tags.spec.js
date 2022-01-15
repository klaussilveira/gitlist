describe('Repository tags page', () => {
  it('successfully loads', () => {
    cy.visit('/git-bare-repo/tags');

    cy.get('.card-header').should('have.text', '\n        Remote tags\n      ');
    cy.get('h5 > a').should('have.text', '\n                    1.2\n                  ');
  });
});
